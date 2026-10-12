<?php

namespace App\Services\PolicyIngestion;

use App\Contracts\PolicyIngestion\DocumentChunker;
use App\Exceptions\PolicyIngestion\UnsupportedChunkFormatException;
class PolicyChunker
{
    /**
     * @param iterable<DocumentChunker> $chunkers
     */
    public function __construct(
        private iterable $chunkers
    ) {
    }

    public function chunk(string $text, string $format): array
    {
        foreach ($this->chunkers as $chunker) {
            if ($chunker->supports($format)) {
                return $chunker->chunk($text);
            }
        }

        throw new UnsupportedChunkFormatException($format);
    }
}