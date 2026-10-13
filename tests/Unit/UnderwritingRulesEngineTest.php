<?php

namespace Tests\Unit;

use App\Domain\Underwriting\UnderwritingRulesEngine;
use PHPUnit\Framework\TestCase;

class UnderwritingRulesEngineTest extends TestCase
{
    private UnderwritingRulesEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = new UnderwritingRulesEngine();
    }

    public function test_returns_eligible_when_all_rules_pass(): void
    {
        $decision = $this->engine->evaluate(
            netMonthlyIncome: 30000,
            existingMonthlyObligations: 4000,
            monthlyInstallment: 8630.39,
            dbrPercentage: 42.10,
            maxDbrPercentage: 45,
            bureauScore: 712,
            minimumBureauScore: 650,
            ageAtMaturity: 42.92,
            maximumAgeAtMaturity: 60,
            requestedTenorMonths: 60,
            maximumTenorMonths: 60,
            requestedAmount: 300000,
            minimumAmount: 20000,
            maximumAmount: 1000000,
        );

        $this->assertSame('eligible', $decision->decision);
    }

    public function test_returns_refer_when_bureau_score_is_below_threshold(): void
    {
        $decision = $this->engine->evaluate(
            netMonthlyIncome: 30000,
            existingMonthlyObligations: 4000,
            monthlyInstallment: 8630.39,
            dbrPercentage: 42.10,
            maxDbrPercentage: 45,
            bureauScore: 580,
            minimumBureauScore: 650,
            ageAtMaturity: 42.92,
            maximumAgeAtMaturity: 60,
            requestedTenorMonths: 60,
            maximumTenorMonths: 60,
            requestedAmount: 300000,
            minimumAmount: 20000,
            maximumAmount: 1000000,
        );

        $this->assertSame('refer', $decision->decision);
    }

    public function test_returns_decline_when_dbr_exceeds_limit(): void
    {
        $decision = $this->engine->evaluate(
            netMonthlyIncome: 30000,
            existingMonthlyObligations: 4000,
            monthlyInstallment: 12000,
            dbrPercentage: 53.33,
            maxDbrPercentage: 45,
            bureauScore: 712,
            minimumBureauScore: 650,
            ageAtMaturity: 42.92,
            maximumAgeAtMaturity: 60,
            requestedTenorMonths: 60,
            maximumTenorMonths: 60,
            requestedAmount: 300000,
            minimumAmount: 20000,
            maximumAmount: 1000000,
        );

        $this->assertSame('decline', $decision->decision);
    }

    public function test_returns_decline_when_age_at_maturity_exceeds_limit(): void
    {
        $decision = $this->engine->evaluate(
            netMonthlyIncome: 30000,
            existingMonthlyObligations: 4000,
            monthlyInstallment: 8630.39,
            dbrPercentage: 42.10,
            maxDbrPercentage: 45,
            bureauScore: 712,
            minimumBureauScore: 650,
            ageAtMaturity: 61,
            maximumAgeAtMaturity: 60,
            requestedTenorMonths: 60,
            maximumTenorMonths: 60,
            requestedAmount: 300000,
            minimumAmount: 20000,
            maximumAmount: 1000000,
        );

        $this->assertSame('decline', $decision->decision);
    }

    public function test_returns_decline_when_tenor_exceeds_limit(): void
    {
        $decision = $this->engine->evaluate(
            netMonthlyIncome: 30000,
            existingMonthlyObligations: 4000,
            monthlyInstallment: 8630.39,
            dbrPercentage: 42.10,
            maxDbrPercentage: 45,
            bureauScore: 712,
            minimumBureauScore: 650,
            ageAtMaturity: 42.92,
            maximumAgeAtMaturity: 60,
            requestedTenorMonths: 72,
            maximumTenorMonths: 60,
            requestedAmount: 300000,
            minimumAmount: 20000,
            maximumAmount: 1000000,
        );

        $this->assertSame('decline', $decision->decision);
    }

    public function test_returns_decline_when_amount_is_outside_product_range(): void
    {
        $decision = $this->engine->evaluate(
            netMonthlyIncome: 30000,
            existingMonthlyObligations: 4000,
            monthlyInstallment: 8630.39,
            dbrPercentage: 42.10,
            maxDbrPercentage: 45,
            bureauScore: 712,
            minimumBureauScore: 650,
            ageAtMaturity: 42.92,
            maximumAgeAtMaturity: 60,
            requestedTenorMonths: 60,
            maximumTenorMonths: 60,
            requestedAmount: 1500000,
            minimumAmount: 20000,
            maximumAmount: 1000000,
        );

        $this->assertSame('decline', $decision->decision);
    }

    public function test_fail_has_priority_over_refer(): void
    {
        $decision = $this->engine->evaluate(
            netMonthlyIncome: 30000,
            existingMonthlyObligations: 4000,
            monthlyInstallment: 12000,
            dbrPercentage: 53.33,
            maxDbrPercentage: 45,
            bureauScore: 580,
            minimumBureauScore: 650,
            ageAtMaturity: 42.92,
            maximumAgeAtMaturity: 60,
            requestedTenorMonths: 60,
            maximumTenorMonths: 60,
            requestedAmount: 300000,
            minimumAmount: 20000,
            maximumAmount: 1000000,
        );

        $this->assertSame('decline', $decision->decision);
    }
}