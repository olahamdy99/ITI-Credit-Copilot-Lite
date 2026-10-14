<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Models\LoanApplication;
use App\Models\User;
use App\Services\Approval\ApprovalService;
use Illuminate\Console\Command;
use Throwable;

class RejectAssessment extends Command
{
    protected $signature = 'assessment:reject
                            {reference : Loan application reference code}
                            {email : Credit Officer email}
                            {--comment= : Rejection comment}';

    protected $description = 'Reject the latest pending assessment for a loan application';

    public function handle(ApprovalService $approvalService): int
    {
        try {
            $application = LoanApplication::query()
                ->where('reference_code', $this->argument('reference'))
                ->first();

            if (! $application) {
                $this->error('Loan application not found.');

                return self::FAILURE;
            }

            $assessment = Assessment::query()
                ->where('loan_application_id', $application->id)
                ->latest('id')
                ->first();

            if (! $assessment) {
                $this->error('No assessment found for this application.');

                return self::FAILURE;
            }

            $user = User::query()
                ->where('email', $this->argument('email'))
                ->first();

            if (! $user) {
                $this->error('Credit Officer not found.');

                return self::FAILURE;
            }

            $approval = $approvalService->reject(
                assessment: $assessment,
                user: $user,
                comment: $this->option('comment')
            );

            $this->info('Assessment rejected successfully.');
            $this->line('Assessment ID: '.$assessment->id);
            $this->line('Application: '.$application->reference_code);
            $this->line('Rejected by: '.$user->email);
            $this->line('Status: '.$approval->assessment->status);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
