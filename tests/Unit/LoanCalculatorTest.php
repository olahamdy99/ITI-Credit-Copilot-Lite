<?php

namespace Tests\Unit;

use App\Domain\Credit\LoanCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class LoanCalculatorTest extends TestCase
{
    private LoanCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new LoanCalculator();
    }

    public function test_monthly_installment_matches_worked_example(): void
    {
        $result = $this->calculator->monthlyInstallment(
            principal: 300000,
            annualRatePercent: 24,
            tenorMonths: 60
        );

        $this->assertSame(8630.39, $result);
    }

    public function test_dbr_matches_worked_example(): void
    {
        $result = $this->calculator->dbrPercentage(
            netMonthlyIncome: 30000,
            existingMonthlyObligations: 4000,
            newMonthlyInstallment: 8630.39
        );

        $this->assertSame(42.10, $result);
    }

    public function test_max_eligible_amount_matches_worked_example(): void
    {
        $result = $this->calculator->maxEligibleAmount(
            netMonthlyIncome: 30000,
            existingMonthlyObligations: 4000,
            maxDbrPercentage: 50,
            annualRatePercent: 24,
            tenorMonths: 60
        );

        $this->assertSame(382000, $result);
    }

    public function test_age_at_maturity_is_calculated_deterministically(): void
    {
        $result = $this->calculator->ageAtMaturity(
            dateOfBirth: new DateTimeImmutable('1987-04-12'),
            applicationDate: new DateTimeImmutable('2025-04-03'),
            tenorMonths: 60
        );

        $this->assertSame(42.92, $result);
    }

    public function test_zero_interest_rate_is_supported(): void
    {
        $result = $this->calculator->monthlyInstallment(
            principal: 120000,
            annualRatePercent: 0,
            tenorMonths: 12
        );

        $this->assertSame(10000.00, $result);
    }

    public function test_monthly_installment_rejects_zero_principal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->monthlyInstallment(
            principal: 0,
            annualRatePercent: 24,
            tenorMonths: 60
        );
    }

    public function test_monthly_installment_rejects_zero_tenor(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->monthlyInstallment(
            principal: 300000,
            annualRatePercent: 24,
            tenorMonths: 0
        );
    }

    public function test_monthly_installment_rejects_negative_interest_rate(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->monthlyInstallment(
            principal: 300000,
            annualRatePercent: -1,
            tenorMonths: 60
        );
    }

    public function test_dbr_rejects_zero_income(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->dbrPercentage(
            netMonthlyIncome: 0,
            existingMonthlyObligations: 4000,
            newMonthlyInstallment: 8000
        );
    }

    public function test_max_eligible_returns_zero_when_existing_obligations_exceed_limit(): void
    {
        $result = $this->calculator->maxEligibleAmount(
            netMonthlyIncome: 10000,
            existingMonthlyObligations: 6000,
            maxDbrPercentage: 50,
            annualRatePercent: 24,
            tenorMonths: 60
        );

        $this->assertSame(0, $result);
    }
}