<?php

namespace App\DTOs\Underwriting;

class UnderwritingPolicyTerms
{
    public function __construct(
        public readonly float $maxDbrPercentage,
        public readonly float $minimumNetMonthlyIncome,
        public readonly int $minimumEmploymentMonths,
        public readonly int $minimumBureauScore,
        public readonly float $maximumAgeAtMaturity,
        public readonly int $minimumTenorMonths,
        public readonly int $maximumTenorMonths,
        public readonly float $minimumAmount,
        public readonly float $maximumAmount,
    ) {
    }
}