<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class IngestPolicyDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy:ingest';

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
        \App\Services\PolicyIngestion\PolicyIngestionService $service
    ): int {
        $documents = \App\Models\PolicyDocument::query()
            ->orderBy('id')
            ->get();

        foreach ($documents as $document) {
            $this->info(
                "Ingesting: {$document->title}"
            );

            try {
                $service->ingest($document);

                $this->info('Completed.');
            } catch (\Throwable $exception) {
                $this->error(
                    "Failed: {$exception->getMessage()}"
                );
            }
        }

        return self::SUCCESS;
    }
}
