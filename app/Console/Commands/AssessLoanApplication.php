<?php

namespace App\Console\Commands;

use App\Models\LoanApplication;
use App\Services\Underwriting\UnderwritingAssessmentService;
use Illuminate\Console\Command;
use Throwable;

class AssessLoanApplication extends Command
{
    protected $signature = 'application:assess
        {reference : Application reference code}';

    protected $description =
        'Run deterministic underwriting assessment for a loan application';

    public function handle(
        UnderwritingAssessmentService $service
    ): int {
        try {
            $application = LoanApplication::where(
                'reference_code',
                $this->argument('reference')
            )->firstOrFail();

            $assessment = $service->assess($application);

            $this->info('Assessment completed successfully.');

            $this->table(
                ['Field', 'Value'],
                [
                    ['Application', $application->reference_code],
                    ['Policy edition', $assessment->policyEdition?->code],
                    ['Annual rate', $assessment->annual_rate_percent . '%'],
                    ['Monthly installment', $assessment->monthly_installment],
                    ['DBR', $assessment->debt_burden_ratio . '%'],
                    ['Maximum eligible amount', $assessment->maximum_eligible_amount],
                    ['Recommendation', $assessment->recommendation],
                    ['Recommended amount', $assessment->recommended_amount],
                    ['Status', $assessment->status],
                    ['Run ID', $assessment->run_id],
                ]
            );

            $this->newLine();
            $this->info('Rule results:');

            foreach ($assessment->ruleResults as $rule) {
                $this->line(
                    sprintf(
                        '%s: %s — %s',
                        $rule->rule_name,
                        strtoupper($rule->result),
                        $rule->reason
                    )
                );
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                'Assessment failed: ' . $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}