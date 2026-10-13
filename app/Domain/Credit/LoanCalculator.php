<?php

namespace App\Domain\Credit;

use DateTimeInterface;
use InvalidArgumentException;

class LoanCalculator
{
    public function monthlyInstallment(
        float $principal,
        float $annualRatePercent,
        int $tenorMonths
    ): float {
        if ($principal <= 0) {
            throw new InvalidArgumentException(
                'Principal must be greater than zero.'
            );
        }

        if ($tenorMonths <= 0) {
            throw new InvalidArgumentException(
                'Tenor must be greater than zero.'
            );
        }

        if ($annualRatePercent < 0) {
            throw new InvalidArgumentException(
                'Annual interest rate cannot be negative.'
            );
        }

        if ($annualRatePercent === 0.0) {
            return $this->roundMoney(
                $principal / $tenorMonths
            );
        }

        $monthlyRate = ($annualRatePercent / 100) / 12;

        $installment = (
            $principal * $monthlyRate
        ) / (
            1 - pow(
                1 + $monthlyRate,
                -$tenorMonths
            )
        );

        return $this->roundMoney($installment);
    }

    public function dbrPercentage(
        float $netMonthlyIncome,
        float $existingMonthlyObligations,
        float $newMonthlyInstallment
    ): float {
        if ($netMonthlyIncome <= 0) {
            throw new InvalidArgumentException(
                'Net monthly income must be greater than zero.'
            );
        }

        $dbr = (
            ($existingMonthlyObligations + $newMonthlyInstallment)
            / $netMonthlyIncome
        ) * 100;

        return $this->roundPercentage($dbr);
    }

    public function maxEligibleAmount(
        float $netMonthlyIncome,
        float $existingMonthlyObligations,
        float $maxDbrPercentage,
        float $annualRatePercent,
        int $tenorMonths
    ): int {
        if ($netMonthlyIncome <= 0) {
            throw new InvalidArgumentException(
                'Net monthly income must be greater than zero.'
            );
        }

        $maximumTotalDebtPayment =
            $netMonthlyIncome * ($maxDbrPercentage / 100);

        $availableInstallment =
            $maximumTotalDebtPayment
            - $existingMonthlyObligations;

        if ($availableInstallment <= 0) {
            return 0;
        }

        $monthlyRate = ($annualRatePercent / 100) / 12;

        if ($monthlyRate === 0.0) {
            $principal =
                $availableInstallment * $tenorMonths;
        } else {
            $principal =
                $availableInstallment
                * (
                    1 - pow(
                        1 + $monthlyRate,
                        -$tenorMonths
                    )
                )
                / $monthlyRate;
        }

        return (int) (
            floor($principal / 1000) * 1000
        );
    }

    public function ageAtMaturity(
        DateTimeInterface $dateOfBirth,
        DateTimeInterface $applicationDate,
        int $tenorMonths
    ): float {
        $maturityDate = (
            clone $applicationDate
        )->modify("+{$tenorMonths} months");

        $age = $dateOfBirth->diff(
            $maturityDate
        );

        return round(
            $age->y + ($age->m / 12),
            2
        );
    }

    private function roundMoney(float $value): float
    {
        return round(
            $value,
            2,
            PHP_ROUND_HALF_UP
        );
    }

    private function roundPercentage(float $value): float
    {
        return round(
            $value,
            2,
            PHP_ROUND_HALF_UP
        );
    }
}