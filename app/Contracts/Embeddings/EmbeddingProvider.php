<?php

namespace App\Contracts\Embeddings;

interface EmbeddingProvider
{
    /**
     * @return array<float>
     */
    public function embed(string $text): array;

    public function dimension(): int;
}