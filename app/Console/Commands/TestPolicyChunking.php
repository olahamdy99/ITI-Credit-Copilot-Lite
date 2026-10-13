<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestPolicyChunking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy:test-chunking';

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
        \App\Services\PolicyIngestion\PolicyTextExtractor $extractor,
        \App\Services\PolicyIngestion\Chunkers\SectionChunker $chunker
    ): int {
        $path = base_path(
            'corpus/policy/credit-policy-2024.pdf'
        );

        $text = $extractor->extract(
            $path,
            'pdf'
        );

        $chunks = $chunker->chunk($text);

        foreach ($chunks as $chunk) {
            $this->line('----------------------------------------');
            $this->line("Chunk Index: {$chunk['chunk_index']}");
            $this->line("Section ID: {$chunk['section_id']}");
            $this->line(
                'Clause ID: '.($chunk['clause_id'] ?? 'NULL')
            );
            $this->line("Title: {$chunk['section_title']}");
            $this->line('Content:');
            $this->line($chunk['content']);
            $this->newLine();
        }

        $this->info(
            'Total chunks: '.count($chunks)
        );

        return self::SUCCESS;
    }
}
