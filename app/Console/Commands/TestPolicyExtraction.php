<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestPolicyExtraction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy:test-extraction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\PolicyIngestion\PolicyTextExtractor $extractor): int
    {
        $documents = [
            [
                'path' => base_path(
                    'corpus/policy/product-sheet-personal-loan.md'
                ),
                'format' => 'markdown',
                'label' => 'Markdown',
            ],
            [
                'path' => base_path(
                    'corpus/policy/pricing-table.csv'
                ),
                'format' => 'csv',
                'label' => 'CSV',
            ],
            [
                'path' => base_path(
                    'corpus/policy/credit-policy-2024.pdf'
                ),
                'format' => 'pdf',
                'label' => 'PDF',
            ],
        ];

        foreach ($documents as $document) {
            $this->info(
                "Testing {$document['label']} extraction..."
            );

            $text = $extractor->extract(
                $document['path'],
                $document['format']
            );

            $this->line($text);

            $this->newLine();
        }

        return self::SUCCESS;
    }
}
