<?php

namespace App\Services\PolicyIngestion;

use App\Exceptions\PolicyIngestion\EmptyDocumentChunksException;
use App\Models\PolicyDocument;
use Illuminate\Support\Facades\DB;
use Throwable;

class PolicyIngestionService
{
    public function __construct(
        private PolicyTextExtractor $textExtractor,
        private PolicyChunker $chunker,
    ) {
    }

    public function ingest(PolicyDocument $document): void
    {
        $document->update([
            'processing_status' => 'processing',
            'failure_reason' => null,
        ]);

        try {
            $path = base_path($document->source_file);

            $text = $this->textExtractor->extract(
                $path,
                $document->format
            );

            $chunks = $this->chunker->chunk(
                $text,
                $document->format
            );

            if ($chunks === []) {
                throw new EmptyDocumentChunksException(
                    $document->title
                );
            }

            DB::transaction(function () use ($document, $chunks) {
                // Makes re-ingestion idempotent.
                $document->chunks()->delete();

                foreach ($chunks as $chunk) {
                    $document->chunks()->create([
                        'policy_edition_id' =>
                            $document->policy_edition_id,

                        'chunk_index' =>
                            $chunk['chunk_index'],

                        'section_id' =>
                            $chunk['section_id'],

                        'section_title' =>
                            $chunk['section_title'],

                        'clause_id' =>
                            $chunk['clause_id'],

                        'page_number' => null,

                        'content' =>
                            $chunk['content'],

                        'effective_from' =>
                            $document->effective_from,

                        'effective_to' =>
                            $document->effective_to,

                        'metadata' => [
                            'source_file' =>
                                $document->source_file,

                            'document_type' =>
                                $document->type,

                            'document_title' =>
                                $document->title,

                            'format' =>
                                $document->format,
                        ],
                    ]);
                }

                $document->update([
                    'processing_status' => 'completed',
                    'failure_reason' => null,
                ]);
            });
        } catch (Throwable $exception) {
            $document->update([
                'processing_status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}