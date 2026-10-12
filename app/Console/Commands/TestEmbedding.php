<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestEmbedding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embedding:test';

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
        \App\Contracts\Embeddings\EmbeddingProvider $provider
    ): int {
        $text = 'What is the maximum debt burden ratio?';

        $this->info('Generating embedding...');

        $embedding = $provider->embed($text);

        $this->info(
            'Embedding dimensions: ' . count($embedding)
        );

        $this->line(
            'First 5 values: ' .
            implode(', ', array_slice($embedding, 0, 5))
        );

        return self::SUCCESS;
    }
}
