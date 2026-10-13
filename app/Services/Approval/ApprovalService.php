<?php

namespace App\Services\Approval;

use App\Exceptions\Approval\AuthorityLimitExceededException;
use App\Exceptions\Approval\InvalidAssessmentStatusException;
use App\Exceptions\Approval\UnauthorizedApproverException;
use App\Models\Approval;
use App\Models\Assessment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function approve(
        Assessment $assessment,
        User $user,
        ?string $comment = null
    ): Approval {
        $this->ensureCanDecide(
            assessment: $assessment,
            user: $user,
        );

        $recommendedAmount = (float) $assessment->recommended_amount;
        $authorityLimit = (float) $user->authority_limit;

        if ($recommendedAmount > $authorityLimit) {
            throw new AuthorityLimitExceededException(
                sprintf(
                    'Recommended amount %.2f exceeds approver authority limit %.2f.',
                    $recommendedAmount,
                    $authorityLimit
                )
            );
        }

        return DB::transaction(function () use (
            $assessment,
            $user,
            $comment,
            $authorityLimit
        ) {
            $approval = Approval::create([
                'assessment_id' => $assessment->id,
                'approved_by' => $user->id,
                'decision' => 'approved',
                'comment' => $comment,
                'decided_at' => now(),
                'authority_limit' => $authorityLimit,
            ]);

            $assessment->update([
                'status' => 'approved',
            ]);

            return $approval->fresh([
                'assessment',
                'approver',
            ]);
        });
    }

    public function reject(
        Assessment $assessment,
        User $user,
        ?string $comment = null
    ): Approval {
        $this->ensureCanDecide(
            assessment: $assessment,
            user: $user,
        );

        return DB::transaction(function () use (
            $assessment,
            $user,
            $comment
        ) {
            $approval = Approval::create([
                'assessment_id' => $assessment->id,
                'approved_by' => $user->id,
                'decision' => 'rejected',
                'comment' => $comment,
                'decided_at' => now(),
                'authority_limit' => $user->authority_limit,
            ]);

            $assessment->update([
                'status' => 'rejected',
            ]);

            return $approval->fresh([
                'assessment',
                'approver',
            ]);
        });
    }

    private function ensureCanDecide(
        Assessment $assessment,
        User $user
    ): void {
        if ($user->role !== 'credit_officer') {
            throw new UnauthorizedApproverException(
                'Only a credit officer may approve or reject an assessment.'
            );
        }

        if ($assessment->status !== 'pending_approval') {
            throw new InvalidAssessmentStatusException(
                sprintf(
                    'Assessment cannot be decided from status [%s].',
                    $assessment->status
                )
            );
        }
    }

    public function issue(
        Assessment $assessment
    ): Assessment {
        if ($assessment->status !== 'approved') {
            throw new InvalidAssessmentStatusException(
                sprintf(
                    'Assessment cannot be issued from status [%s].',
                    $assessment->status
                )
            );
        }

        $assessment->update([
            'status' => 'issued',
        ]);

        return $assessment->fresh();
    }
}
