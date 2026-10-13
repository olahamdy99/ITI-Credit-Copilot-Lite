<?php

namespace App\Domain\Underwriting;

use App\DTOs\Underwriting\RuleResult;
use App\DTOs\Underwriting\UnderwritingDecision;

class UnderwritingRulesEngine
{
    public function evaluate(
        float $netMonthlyIncome,
        float $existingMonthlyObligations,
        float $monthlyInstallment,
        float $dbrPercentage,
        float $maxDbrPercentage,
        float $minimumNetMonthlyIncome,
        int $employmentMonths,
        int $minimumEmploymentMonths,
        int $bureauScore,
        int $minimumBureauScore,
        float $ageAtMaturity,
        float $maximumAgeAtMaturity,
        int $requestedTenorMonths,
        int $minimumTenorMonths,
        int $maximumTenorMonths,
        float $requestedAmount,
        float $minimumAmount,
        float $maximumAmount,
    ): UnderwritingDecision {
        $rules = [];

        /*
         * Compare the exact DBR value.
         * $dbrPercentage is kept for display/storage only.
         */
        $exactDbrPercentage = (
            (
                $existingMonthlyObligations
                + $monthlyInstallment
            )
            / $netMonthlyIncome
        ) * 100;

        $rules[] = $this->checkDbr(
            $exactDbrPercentage,
            $maxDbrPercentage
        );

        $rules[] = $this->checkMinimumIncome(
            $netMonthlyIncome,
            $minimumNetMonthlyIncome
        );

        $rules[] = $this->checkEmploymentDuration(
            $employmentMonths,
            $minimumEmploymentMonths
        );

        $rules[] = $this->checkBureauScore(
            $bureauScore,
            $minimumBureauScore
        );

        $rules[] = $this->checkAgeAtMaturity(
            $ageAtMaturity,
            $maximumAgeAtMaturity
        );

        $rules[] = $this->checkTenor(
            $requestedTenorMonths,
            $minimumTenorMonths,
            $maximumTenorMonths
        );

        $rules[] = $this->checkAmount(
            $requestedAmount,
            $minimumAmount,
            $maximumAmount
        );

        return new UnderwritingDecision(
            decision: $this->resolveDecision($rules),
            rules: $rules,
        );
    }

    private function checkDbr(
        float $actual,
        float $maximum
    ): RuleResult {
        if ($actual > $maximum) {
            return new RuleResult(
                rule: 'dbr',
                status: 'fail',
                message: 'Debt burden ratio exceeds the permitted maximum.',
                details: [
                    'actual' => $actual,
                    'maximum' => $maximum,
                ]
            );
        }

        return new RuleResult(
            rule: 'dbr',
            status: 'pass',
            message: 'Debt burden ratio is within the permitted maximum.',
            details: [
                'actual' => $actual,
                'maximum' => $maximum,
            ]
        );
    }

    private function checkMinimumIncome(
        float $actual,
        float $minimum
    ): RuleResult {
        if ($actual < $minimum) {
            return new RuleResult(
                rule: 'minimum_income',
                status: 'fail',
                message: 'Net monthly income is below the policy minimum.',
                details: [
                    'actual' => $actual,
                    'minimum' => $minimum,
                ]
            );
        }

        return new RuleResult(
            rule: 'minimum_income',
            status: 'pass',
            message: 'Net monthly income meets the policy minimum.',
            details: [
                'actual' => $actual,
                'minimum' => $minimum,
            ]
        );
    }

    private function checkEmploymentDuration(
        int $actualMonths,
        int $minimumMonths
    ): RuleResult {
        if ($actualMonths < $minimumMonths) {
            return new RuleResult(
                rule: 'employment_duration',
                status: 'fail',
                message: 'Employment duration is below the policy minimum.',
                details: [
                    'actual_months' => $actualMonths,
                    'minimum_months' => $minimumMonths,
                ]
            );
        }

        return new RuleResult(
            rule: 'employment_duration',
            status: 'pass',
            message: 'Employment duration meets the policy minimum.',
            details: [
                'actual_months' => $actualMonths,
                'minimum_months' => $minimumMonths,
            ]
        );
    }

    private function checkBureauScore(
        int $actual,
        int $minimum
    ): RuleResult {
        if ($actual < $minimum) {
            return new RuleResult(
                rule: 'bureau_score',
                status: 'refer',
                message: 'Bureau score is below the automatic approval threshold.',
                details: [
                    'actual' => $actual,
                    'minimum' => $minimum,
                ]
            );
        }

        return new RuleResult(
            rule: 'bureau_score',
            status: 'pass',
            message: 'Bureau score meets the automatic approval threshold.',
            details: [
                'actual' => $actual,
                'minimum' => $minimum,
            ]
        );
    }

    private function checkAgeAtMaturity(
        float $actual,
        float $maximum
    ): RuleResult {
        if ($actual > $maximum) {
            return new RuleResult(
                rule: 'age_at_maturity',
                status: 'fail',
                message: 'Applicant exceeds the maximum permitted age at maturity.',
                details: [
                    'actual' => $actual,
                    'maximum' => $maximum,
                ]
            );
        }

        return new RuleResult(
            rule: 'age_at_maturity',
            status: 'pass',
            message: 'Applicant age at maturity is within the permitted limit.',
            details: [
                'actual' => $actual,
                'maximum' => $maximum,
            ]
        );
    }

    private function checkTenor(
        int $actual,
        int $minimum,
        int $maximum
    ): RuleResult {
        if ($actual < $minimum || $actual > $maximum) {
            return new RuleResult(
                rule: 'tenor',
                status: 'fail',
                message: 'Requested tenor is outside the permitted product range.',
                details: [
                    'actual' => $actual,
                    'minimum' => $minimum,
                    'maximum' => $maximum,
                ]
            );
        }

        return new RuleResult(
            rule: 'tenor',
            status: 'pass',
            message: 'Requested tenor is within the permitted product range.',
            details: [
                'actual' => $actual,
                'minimum' => $minimum,
                'maximum' => $maximum,
            ]
        );
    }

    private function checkAmount(
        float $actual,
        float $minimum,
        float $maximum
    ): RuleResult {
        if ($actual < $minimum || $actual > $maximum) {
            return new RuleResult(
                rule: 'amount',
                status: 'fail',
                message: 'Requested amount is outside the permitted product range.',
                details: [
                    'actual' => $actual,
                    'minimum' => $minimum,
                    'maximum' => $maximum,
                ]
            );
        }

        return new RuleResult(
            rule: 'amount',
            status: 'pass',
            message: 'Requested amount is within the permitted product range.',
            details: [
                'actual' => $actual,
                'minimum' => $minimum,
                'maximum' => $maximum,
            ]
        );
    }

    private function resolveDecision(array $rules): string
    {
        foreach ($rules as $rule) {
            if ($rule->status === 'fail') {
                return 'decline';
            }
        }

        foreach ($rules as $rule) {
            if ($rule->status === 'refer') {
                return 'refer';
            }
        }

        return 'eligible';
    }
}
