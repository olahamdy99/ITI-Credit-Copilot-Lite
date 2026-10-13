<?php

namespace App\Services\Underwriting;

use App\Domain\Credit\LoanCalculator;
use App\Domain\Underwriting\UnderwritingRulesEngine;
use App\Models\Assessment;
use App\Models\AssessmentRuleResult;
use App\Models\LoanApplication;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class UnderwritingAssessmentService
{
    public function __construct(
        private PolicyTermsResolver $policyTermsResolver,
        private LoanCalculator $loanCalculator,
        private UnderwritingRulesEngine $rulesEngine,
        private PricingRateResolver $pricingRateResolver,
    ) {}

    public function assess(LoanApplication $application): Assessment
    {
        $application->loadMissing([
            'policyEdition',
            'extraction',
        ]);

        if (! $application->policyEdition) {
            throw new RuntimeException(
                'Loan application has no policy edition.'
            );
        }

        if (! $application->extraction) {
            throw new RuntimeException(
                'Loan application has no completed extraction.'
            );
        }

        if ($application->extraction->status !== 'completed') {
            throw new RuntimeException(
                'Application extraction is not completed.'
            );
        }

        if (! $application->extraction->pricing_segment) {
            throw new RuntimeException(
                'Application has no pricing segment.'
            );
        }

        /*
         * Resolve policy limits from the policy edition
         * that was active on the application date.
         */
        $terms = $this->policyTermsResolver->resolve(
            $application->policyEdition
        );

        /*
         * Calculate employment duration in completed months.
         */
        $employmentStartDate = new DateTimeImmutable(
            $application->extraction
                ->employment_start_date
                ->format('Y-m-d')
        );

        $applicationDate = new DateTimeImmutable(
            $application->application_date->format('Y-m-d')
        );

        if ($employmentStartDate > $applicationDate) {
            throw new RuntimeException(
                'Employment start date cannot be after application date.'
            );
        }

        $employmentInterval = $employmentStartDate->diff(
            $applicationDate
        );

        $employmentMonths =
            ($employmentInterval->y * 12)
            + $employmentInterval->m;

        /*
         * Rate comes from the pricing table,
         * not from hard-coded source values.
         */
        $annualRatePercent = $this->pricingRateResolver->resolve(
            tenorMonths: $application->requested_tenor_months,
            segment: $application->extraction->pricing_segment,
            applicationDate: $applicationDate->format('Y-m-d'),
        );

        /*
         * Deterministic calculations.
         */
        $monthlyInstallment = $this->loanCalculator->monthlyInstallment(
            principal: (float) $application->requested_amount,
            annualRatePercent: $annualRatePercent,
            tenorMonths: $application->requested_tenor_months,
        );

        /*
         * This rounded value is used for display/storage.
         * The rules engine calculates the exact DBR itself
         * for the policy comparison.
         */
        $dbrPercentage = $this->loanCalculator->dbrPercentage(
            netMonthlyIncome: (float) $application->extraction->net_monthly_income,
            existingMonthlyObligations: (float) $application->extraction->existing_monthly_obligations,
            newMonthlyInstallment: $monthlyInstallment,
        );

        $maximumEligibleAmount =
            $this->loanCalculator->maxEligibleAmount(
                netMonthlyIncome: (float) $application->extraction->net_monthly_income,
                existingMonthlyObligations: (float) $application->extraction->existing_monthly_obligations,
                maxDbrPercentage: $terms->maxDbrPercentage,
                annualRatePercent: $annualRatePercent,
                tenorMonths: $application->requested_tenor_months,
            );

        $ageAtMaturity = $this->loanCalculator->ageAtMaturity(
            dateOfBirth: $application->date_of_birth,
            applicationDate: $application->application_date,
            tenorMonths: $application->requested_tenor_months,
        );

        /*
         * Run deterministic underwriting rules.
         */
        $decision = $this->rulesEngine->evaluate(
            netMonthlyIncome: (float) $application->extraction->net_monthly_income,

            existingMonthlyObligations: (float) $application->extraction->existing_monthly_obligations,

            monthlyInstallment: $monthlyInstallment,

            dbrPercentage: $dbrPercentage,

            maxDbrPercentage: $terms->maxDbrPercentage,

            minimumNetMonthlyIncome: $terms->minimumNetMonthlyIncome,

            employmentMonths: $employmentMonths,

            minimumEmploymentMonths: $terms->minimumEmploymentMonths,

            bureauScore: $application->extraction->bureau_score,

            minimumBureauScore: $terms->minimumBureauScore,

            ageAtMaturity: $ageAtMaturity,

            maximumAgeAtMaturity: $terms->maximumAgeAtMaturity,

            requestedTenorMonths: $application->requested_tenor_months,

            minimumTenorMonths: $terms->minimumTenorMonths,

            maximumTenorMonths: $terms->maximumTenorMonths,

            requestedAmount: (float) $application->requested_amount,

            minimumAmount: $terms->minimumAmount,

            maximumAmount: $terms->maximumAmount,
        );

        return DB::transaction(function () use (
            $application,
            $annualRatePercent,
            $monthlyInstallment,
            $dbrPercentage,
            $maximumEligibleAmount,
            $decision
        ) {
            $recommendedAmount = match ($decision->decision) {
                'eligible' => (float) $application->requested_amount,

                'refer' => min(
                    (float) $application->requested_amount,
                    (float) $maximumEligibleAmount
                ),

                'decline' => 0,
            };

            $assessment = Assessment::create([
                'loan_application_id' => $application->id,
                'policy_edition_id' => $application->policy_edition_id,
                'annual_rate_percent' => $annualRatePercent,
                'monthly_installment' => $monthlyInstallment,
                'debt_burden_ratio' => $dbrPercentage,
                'maximum_eligible_amount' => $maximumEligibleAmount,
                'recommendation' => $decision->decision,
                'recommended_amount' => $recommendedAmount,
                'approval_required_from' => null,
                'status' => 'pending_approval',
                'run_id' => (string) Str::uuid(),
            ]);

            foreach ($decision->rules as $rule) {
                AssessmentRuleResult::create([
                    'assessment_id' => $assessment->id,

                    'rule_code' => $rule->rule,

                    'rule_name' => match ($rule->rule) {
                        'dbr' => 'Debt Burden Ratio',

                        'minimum_income' => 'Minimum Net Monthly Income',

                        'employment_duration' => 'Employment Duration',

                        'bureau_score' => 'Bureau Score',

                        'age_at_maturity' => 'Age at Maturity',

                        'tenor' => 'Requested Tenor',

                        'amount' => 'Requested Amount',

                        default => ucwords(
                            str_replace(
                                '_',
                                ' ',
                                $rule->rule
                            )
                        ),
                    },

                    'result' => $rule->status,
                    'reason' => $rule->message,
                    'citation' => null,
                ]);
            }

            return $assessment->fresh([
                'ruleResults',
                'loanApplication',
                'policyEdition',
            ]);
        });
    }
}
