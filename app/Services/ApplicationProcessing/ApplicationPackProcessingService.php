<?php

namespace App\Services\ApplicationProcessing;

use App\Contracts\ApplicationProcessing\ApplicationPackExtractor;
use App\DTOs\ApplicationProcessing\SanitizedApplicationText;

class ApplicationPackProcessingService
{
    public function __construct(
        private ApplicationPackExtractor $extractor,
        private ApplicationTextSanitizer $sanitizer,
    ) {
    }

    public function process(string $path): SanitizedApplicationText
    {
        $rawText = $this->extractor->extract($path);

        return $this->sanitizer->sanitize($rawText);
    }
}