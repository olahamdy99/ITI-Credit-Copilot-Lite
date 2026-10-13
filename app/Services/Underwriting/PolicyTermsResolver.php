<?php

namespace App\Services\Underwriting;

use App\DTOs\Underwriting\UnderwritingPolicyTerms;
use App\Exceptions\ApplicationProcessingException;
use App\Models\PolicyEdition;

class PolicyTermsResolver
{
    public function resolve(
        PolicyEdition $edition
    ): UnderwritingPolicyTerms {
        return match ($edition->code) {
            'CP-2024' => new UnderwritingPolicyTerms(
                maxDbrPercentage: 50.0,
                minimumNetMonthlyIncome: 8000.0,
                minimumEmploymentMonths: 6,
                minimumBureauScore: 600,
                maximumAgeAtMaturity: 60.0,
                minimumTenorMonths: 12,
                maximumTenorMonths: 60,
                minimumAmount: 20000.0,
                maximumAmount: 1000000.0,
            ),

            'CP-2025' => new UnderwritingPolicyTerms(
                maxDbrPercentage: 45.0,
                minimumNetMonthlyIncome: 10000.0,
                minimumEmploymentMonths: 6,
                minimumBureauScore: 600,
                maximumAgeAtMaturity: 60.0,
                minimumTenorMonths: 12,
                maximumTenorMonths: 60,
                minimumAmount: 20000.0,
                maximumAmount: 1000000.0,
            ),

            default => throw new ApplicationProcessingException(
                "Unsupported policy edition: {$edition->code}"
            ),
        };
    }
}
