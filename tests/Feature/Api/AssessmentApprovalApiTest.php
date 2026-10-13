<?php

namespace Tests\Feature\Api;

use App\Models\Assessment;
use App\Models\LoanApplication;
use App\Models\PolicyEdition;
use App\Models\User;
use App\Services\Underwriting\UnderwritingAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssessmentApprovalApiTest extends TestCase
{
    use RefreshDatabase;

    private function createPolicyEdition(): PolicyEdition
    {
        return PolicyEdition::create([
            'code' => 'CP-TEST',
            'name' => 'Test Credit Policy',
            'effective_from' => '2025-01-01',
            'effective_to' => null,
            'status' => 'active',
        ]);
    }

        private function createLoanApplication(
            PolicyEdition $policyEdition
        ): LoanApplication {
            return LoanApplication::create([
                'reference_code' => 'APP-TEST-001',
                'requested_amount' => 300000,
                'requested_tenor_months' => 60,
                'date_of_birth' => '1990-01-01',
                'application_date' => '2025-04-01',
                'status' => 'submitted',
                'policy_edition_id' => $policyEdition->id,
            ]);
        }

    private function createAssessment(
        LoanApplication $application,
        PolicyEdition $policyEdition,
        string $status = 'pending_approval',
        float $recommendedAmount = 300000
    ): Assessment {
        return Assessment::create([
            'loan_application_id' => $application->id,
            'policy_edition_id' => $policyEdition->id,
            'annual_rate_percent' => 24,
            'monthly_installment' => 8630.39,
            'debt_burden_ratio' => 42.10,
            'maximum_eligible_amount' => 330000,
            'recommendation' => 'eligible',
            'recommended_amount' => $recommendedAmount,
            'approval_required_from' => null,
            'status' => $status,
            'run_id' => fake()->uuid(),
        ]);
    }

    public function test_loan_officer_can_assess_application(): void
    {
        $user = User::factory()->create([
            'role' => 'loan_officer',
            'authority_limit' => 0,
        ]);

        $policyEdition = $this->createPolicyEdition();
        $application = $this->createLoanApplication($policyEdition);

        $assessment = $this->createAssessment(
            $application,
            $policyEdition
        );

        $service = $this->mock(
            UnderwritingAssessmentService::class
        );

        $service->shouldReceive('assess')
            ->once()
            ->andReturn($assessment);

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/applications/{$application->id}/assess"
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Assessment completed successfully.'
            )
            ->assertJsonPath(
                'data.id',
                $assessment->id
            );
    }

    public function test_loan_officer_cannot_approve_assessment(): void
    {
        $user = User::factory()->create([
            'role' => 'loan_officer',
            'authority_limit' => 0,
        ]);

        $policyEdition = $this->createPolicyEdition();
        $application = $this->createLoanApplication($policyEdition);

        $assessment = $this->createAssessment(
            $application,
            $policyEdition
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/assessments/{$assessment->id}/approve",
            [
                'comment' => 'Trying to approve.',
            ]
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'message' =>
                    'You are not authorized to perform this action.',
            ]);
    }

    public function test_credit_officer_can_reject_assessment(): void
    {
        $user = User::factory()->create([
            'role' => 'credit_officer',
            'authority_limit' => 250000,
        ]);

        $policyEdition = $this->createPolicyEdition();
        $application = $this->createLoanApplication($policyEdition);

        $assessment = $this->createAssessment(
            $application,
            $policyEdition,
            'pending_approval',
            0
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/assessments/{$assessment->id}/reject",
            [
                'comment' => 'Rejected after credit review.',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.decision',
                'rejected'
            );

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'status' => 'rejected',
        ]);
    }

    public function test_credit_officer_cannot_approve_above_authority_limit(): void
    {
        $user = User::factory()->create([
            'role' => 'credit_officer',
            'authority_limit' => 250000,
        ]);

        $policyEdition = $this->createPolicyEdition();
        $application = $this->createLoanApplication($policyEdition);

        $assessment = $this->createAssessment(
            $application,
            $policyEdition
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/assessments/{$assessment->id}/approve",
            [
                'comment' => 'Reviewed.',
            ]
        );

        $response
            ->assertForbidden()
            ->assertJsonPath(
                'error',
                'AUTHORITY_LIMIT_EXCEEDED'
            );
    }

    public function test_senior_credit_officer_can_approve_within_authority(): void
    {
        $user = User::factory()->create([
            'role' => 'credit_officer',
            'authority_limit' => 500000,
        ]);

        $policyEdition = $this->createPolicyEdition();
        $application = $this->createLoanApplication($policyEdition);

        $assessment = $this->createAssessment(
            $application,
            $policyEdition
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/assessments/{$assessment->id}/approve",
            [
                'comment' => 'Approved within authority.',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.decision',
                'approved'
            );

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'status' => 'approved',
        ]);
    }

    public function test_approved_assessment_can_be_issued(): void
    {
        $user = User::factory()->create([
            'role' => 'credit_officer',
            'authority_limit' => 500000,
        ]);

        $policyEdition = $this->createPolicyEdition();
        $application = $this->createLoanApplication($policyEdition);

        $assessment = $this->createAssessment(
            $application,
            $policyEdition,
            'approved'
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/assessments/{$assessment->id}/issue"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'issued'
            );

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'status' => 'issued',
        ]);
    }

    public function test_pending_assessment_cannot_be_issued(): void
    {
        $user = User::factory()->create([
            'role' => 'credit_officer',
            'authority_limit' => 500000,
        ]);

        $policyEdition = $this->createPolicyEdition();
        $application = $this->createLoanApplication($policyEdition);

        $assessment = $this->createAssessment(
            $application,
            $policyEdition,
            'pending_approval'
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/assessments/{$assessment->id}/issue"
        );

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'error',
                'INVALID_ASSESSMENT_STATUS'
            );
    }
}