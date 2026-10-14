<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Models\LoanApplication;
use App\Services\Approval\ApprovalService;
use Illuminate\Console\Command;
use Throwable;

class IssueAssessment extends Command
{
    protected $signature = 'assessment:issue
                            {reference : Loan application reference code}';

    protected $description = 'Issue the latest approved assessment for a loan application';

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

            $assessment = $approvalService->issue($assessment);

            $this->info('Assessment issued successfully.');
            $this->line('Assessment ID: '.$assessment->id);
            $this->line('Application: '.$application->reference_code);
            $this->line('Status: '.$assessment->status);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
