<?php

namespace App\Services\ApplicationProcessing;

use App\Contracts\ApplicationProcessing\ApplicationPackExtractor;
use App\DTOs\ApplicationProcessing\SanitizedApplicationText;

class ApplicationPackProcessingService
{
    public function __construct(
        private ApplicationPackExtractor $extractor,
        private ApplicationTextSanitizer $sanitizer,
        private ApplicationIntegrityValidator $integrityValidator,

    ) {
    }

    public function process(string $path): SanitizedApplicationText
    {
        $rawText = $this->extractor->extract($path);

        $this->integrityValidator->validate($rawText);

        return $this->sanitizer->sanitize($rawText);
    }
}