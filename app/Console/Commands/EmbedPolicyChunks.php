<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EmbedPolicyChunks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy:embed {--limit= : Maximum number of chunks to embed}';

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
        \App\Services\Embeddings\PolicyChunkEmbeddingService $service
    ): int {
        $query = \App\Models\PolicyChunk::query()
            ->whereNull('embedding')
            ->orderBy('id');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $chunks = $query->get();

        if ($chunks->isEmpty()) {
            $this->info('No policy chunks require embeddings.');

            return self::SUCCESS;
        }

        $this->info(
            "Generating embeddings for {$chunks->count()} chunks..."
        );

        $bar = $this->output->createProgressBar(
            $chunks->count()
        );

        $bar->start();

        foreach ($chunks as $chunk) {
            try {
                $service->embed($chunk);

                $bar->advance();
            } catch (\Throwable $exception) {
                $bar->finish();

                $this->newLine();

                $this->error(
                    "Failed on chunk {$chunk->id}: "
                    . $exception->getMessage()
                );

                return self::FAILURE;
            }
        }

        $bar->finish();

        $this->newLine(2);

        $this->info('Policy embeddings generated successfully.');

        return self::SUCCESS;
    }
}
