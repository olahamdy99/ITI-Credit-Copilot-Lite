<?php

namespace App\DTOs\Credit;

class LoanCalculationResult
{
    public function __construct(
        public readonly float $monthlyInstallment,
        public readonly float $dbrPercentage,
        public readonly int $maxEligibleAmount,
        public readonly float $ageAtMaturity,
    ) {
    }
}