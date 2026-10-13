<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Services\CreditMemo\CreditMemoService;
use Illuminate\Console\Command;
use Throwable;

class GenerateCreditMemo extends Command
{
    protected $signature = 'application:memo
        {reference : Loan application reference code}';

    protected $description =
        'Generate a grounded credit memo for the latest assessment';

    public function handle(
        CreditMemoService $creditMemoService
    ): int {
        $reference = $this->argument('reference');

        try {
            $assessment = Assessment::query()
                ->whereHas(
                    'loanApplication',
                    fn ($query) => $query->where(
                        'reference_code',
                        $reference
                    )
                )
                ->latest('id')
                ->firstOrFail();

            $memo = $creditMemoService->generate(
                $assessment
            );

            $this->info(
                'Credit memo generated successfully.'
            );

            $this->newLine();

            $this->line($memo->summary);

            $this->newLine();

            $this->table(
                ['Field', 'Value'],
                [
                    ['Assessment ID', $assessment->id],
                    ['Application', $reference],
                    ['Recommendation', $assessment->recommendation],
                    ['Memo status', $memo->status],
                    [
                        'Citations',
                        implode(
                            ', ',
                            $memo->citations ?? []
                        ),
                    ],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                'Credit memo generation failed: '.
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}
