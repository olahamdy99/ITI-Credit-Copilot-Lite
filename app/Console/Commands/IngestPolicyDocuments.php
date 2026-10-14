<?php

namespace App\Console\Commands;

use App\Models\PolicyDocument;
use App\Services\PolicyIngestion\PolicyIngestionService;
use Illuminate\Console\Command;
use Throwable;

class IngestPolicyDocuments extends Command
{
    protected $signature = 'policy:ingest';

    protected $description = 'Extract and chunk all configured policy documents';

    public function handle(
        PolicyIngestionService $service
    ): int {
        $documents = PolicyDocument::query()
            ->orderBy('id')
            ->get();

        $hasFailures = false;

        foreach ($documents as $document) {
            $this->info("Ingesting: {$document->title}");

            try {
                $service->ingest($document);

                $this->info('Completed.');
            } catch (Throwable $exception) {
                $hasFailures = true;

                $this->error(
                    "Failed: {$exception->getMessage()}"
                );
            }
        }

        if ($hasFailures) {
            $this->error(
                'One or more policy documents failed to ingest.'
            );

            return self::FAILURE;
        }

        $this->info(
            'All policy documents ingested successfully.'
        );

        return self::SUCCESS;
    }
}
