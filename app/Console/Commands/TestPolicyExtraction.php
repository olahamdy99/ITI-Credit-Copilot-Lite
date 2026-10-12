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
    public function handle(
        \App\Services\PolicyIngestion\PolicyTextExtractor $extractor
    ): int {
        $markdownPath = base_path(
            'corpus/policy/product-sheet-personal-loan.md'
        );

        $csvPath = base_path(
            'corpus/policy/pricing-table.csv'
        );

        $this->info('Testing Markdown extraction...');
        $markdown = $extractor->extract(
            $markdownPath,
            'markdown'
        );

        $this->line($markdown);

        $this->newLine();

        $this->info('Testing CSV extraction...');
        $csv = $extractor->extract(
            $csvPath,
            'csv'
        );

        $this->line($csv);

        return self::SUCCESS;
    }
}
