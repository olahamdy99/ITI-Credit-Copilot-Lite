<?php

namespace App\DTOs\ApplicationProcessing;

class ExtractedApplicationData
{
    public function __construct(
        public readonly string $referenceCode,
        public readonly string $applicationDate,
        public readonly string $dateOfBirth,
        public readonly float $requestedAmount,
        public readonly int $requestedTenorMonths,
        public readonly string $employmentStartDate,
        public readonly float $netMonthlyIncome,
        public readonly float $existingMonthlyObligations,
        public readonly int $bureauScore,
        public readonly array $evidence,
    ) {
    }
}