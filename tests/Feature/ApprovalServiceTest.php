<?php

namespace Tests\Feature;

use App\Exceptions\Approval\AuthorityLimitExceededException;
use App\Exceptions\Approval\InvalidAssessmentStatusException;
use App\Exceptions\Approval\UnauthorizedApproverException;
use App\Models\Assessment;
use App\Models\LoanApplication;
use App\Models\PolicyEdition;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ApprovalService::class);
    }

    public function test_credit_officer_cannot_approve_above_authority_limit(): void
    {
        $assessment = $this->createAssessment(
            recommendedAmount: 300000
        );

        $officer = $this->createUser(
            role: 'credit_officer',
            authorityLimit: 250000
        );

        $this->expectException(
            AuthorityLimitExceededException::class
        );

        $this->service->approve(
            assessment: $assessment,
            user: $officer,
            comment: 'Attempting approval.'
        );
    }

    public function test_credit_officer_can_approve_within_authority_limit(): void
    {
        $assessment = $this->createAssessment(
            recommendedAmount: 300000
        );

        $officer = $this->createUser(
            role: 'credit_officer',
            authorityLimit: 500000
        );

        $approval = $this->service->approve(
            assessment: $assessment,
            user: $officer,
            comment: 'Approved within authority.'
        );

        $this->assertSame(
            'approved',
            $approval->decision
        );

        $this->assertSame(
            'approved',
            $assessment->fresh()->status
        );

        $this->assertSame(
            '500000.00',
            $approval->authority_limit
        );

        $this->assertSame(
            $officer->id,
            $approval->approved_by
        );

        $this->assertNotNull(
            $approval->decided_at
        );
    }

    public function test_loan_officer_cannot_approve_assessment(): void
    {
        $assessment = $this->createAssessment(
            recommendedAmount: 100000
        );

        $loanOfficer = $this->createUser(
            role: 'loan_officer',
            authorityLimit: 0
        );

        $this->expectException(
            UnauthorizedApproverException::class
        );

        $this->service->approve(
            assessment: $assessment,
            user: $loanOfficer
        );
    }

    public function test_credit_officer_can_reject_pending_assessment(): void
    {
        $assessment = $this->createAssessment(
            recommendedAmount: 300000
        );

        $officer = $this->createUser(
            role: 'credit_officer',
            authorityLimit: 500000
        );

        $approval = $this->service->reject(
            assessment: $assessment,
            user: $officer,
            comment: 'Rejected after review.'
        );

        $this->assertSame(
            'rejected',
            $approval->decision
        );

        $this->assertSame(
            'rejected',
            $assessment->fresh()->status
        );

        $this->assertSame(
            'Rejected after review.',
            $approval->comment
        );

        $this->assertNotNull(
            $approval->decided_at
        );
    }

    public function test_approved_assessment_can_be_issued(): void
    {
        $assessment = $this->createAssessment(
            recommendedAmount: 300000,
            status: 'approved'
        );

        $issued = $this->service->issue(
            $assessment
        );

        $this->assertSame(
            'issued',
            $issued->status
        );
    }

    public function test_pending_assessment_cannot_be_issued(): void
    {
        $assessment = $this->createAssessment(
            recommendedAmount: 300000,
            status: 'pending_approval'
        );

        $this->expectException(
            InvalidAssessmentStatusException::class
        );

        $this->service->issue(
            $assessment
        );
    }

    private function createUser(
        string $role,
        float $authorityLimit
    ): User {
        return User::create([
            'name' => 'Test Officer',
            'email' => uniqid('officer_', true).'@delta.test',
            'password' => 'password',
            'role' => $role,
            'authority_limit' => $authorityLimit,
        ]);
    }

    private function createAssessment(
        float $recommendedAmount,
        string $status = 'pending_approval'
    ): Assessment {
        $edition = PolicyEdition::create([
            'code' => uniqid('CP-TEST-', true),
            'name' => 'Test Credit Policy',
            'effective_from' => '2025-01-01',
            'effective_to' => null,
            'status' => 'active',
        ]);

        $application = LoanApplication::create([
            'reference_code' => uniqid('APP-TEST-', true),
            'requested_amount' => $recommendedAmount,
            'requested_tenor_months' => 60,
            'date_of_birth' => '1990-01-01',
            'application_date' => '2025-04-01',
            'status' => 'submitted',
            'policy_edition_id' => $edition->id,
        ]);

        return Assessment::create([
            'loan_application_id' => $application->id,
            'policy_edition_id' => $edition->id,
            'annual_rate_percent' => 24,
            'monthly_installment' => 8000,
            'debt_burden_ratio' => 40,
            'maximum_eligible_amount' => 350000,
            'recommendation' => 'eligible',
            'recommended_amount' => $recommendedAmount,
            'approval_required_from' => null,
            'status' => $status,
            'run_id' => fake()->uuid(),
        ]);
    }
}
