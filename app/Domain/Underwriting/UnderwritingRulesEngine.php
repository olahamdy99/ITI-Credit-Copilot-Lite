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
        int $bureauScore,
        int $minimumBureauScore,
        float $ageAtMaturity,
        float $maximumAgeAtMaturity,
        int $requestedTenorMonths,
        int $maximumTenorMonths,
        float $requestedAmount,
        float $minimumAmount,
        float $maximumAmount,
    ): UnderwritingDecision {
        $rules = [];

        $rules[] = $this->checkDbr(
            $dbrPercentage,
            $maxDbrPercentage
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
            $maximumTenorMonths
        );

        $rules[] = $this->checkAmount(
            $requestedAmount,
            $minimumAmount,
            $maximumAmount
        );

        $decision = $this->resolveDecision($rules);

        return new UnderwritingDecision(
            decision: $decision,
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
        int $maximum
    ): RuleResult {
        if ($actual > $maximum) {
            return new RuleResult(
                rule: 'tenor',
                status: 'fail',
                message: 'Requested tenor exceeds the permitted maximum.',
                details: [
                    'actual' => $actual,
                    'maximum' => $maximum,
                ]
            );
        }

        return new RuleResult(
            rule: 'tenor',
            status: 'pass',
            message: 'Requested tenor is within the permitted maximum.',
            details: [
                'actual' => $actual,
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