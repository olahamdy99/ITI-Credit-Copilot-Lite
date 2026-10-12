<?php

namespace App\Services\Embeddings;

use App\Contracts\Embeddings\EmbeddingProvider;
use App\Models\PolicyChunk;
use Illuminate\Support\Facades\DB;

class PolicyChunkEmbeddingService
{
    public function __construct(
        private EmbeddingProvider $embeddingProvider,
    ) {
    }

    public function embed(PolicyChunk $chunk): void
    {
        $embedding = $this->embeddingProvider->embed(
            $chunk->content
        );

        $vector = '[' . implode(',', $embedding) . ']';

        DB::update(
            '
                UPDATE policy_chunks
                SET embedding = ?::vector,
                    updated_at = NOW()
                WHERE id = ?
            ',
            [
                $vector,
                $chunk->id,
            ]
        );
    }
}