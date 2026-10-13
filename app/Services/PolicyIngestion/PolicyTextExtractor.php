<?php

namespace App\Services\PolicyIngestion;

use App\Contracts\PolicyIngestion\DocumentExtractor;
use App\Exceptions\PolicyIngestion\UnsupportedDocumentFormatException;

class PolicyTextExtractor
{
    /**
     * @param  iterable<DocumentExtractor>  $extractors
     */
    public function __construct(
        private iterable $extractors
    ) {}

    public function extract(string $path, string $format): string
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($format)) {
                return $extractor->extract($path);
            }
        }

        throw new UnsupportedDocumentFormatException($format);
    }
}
