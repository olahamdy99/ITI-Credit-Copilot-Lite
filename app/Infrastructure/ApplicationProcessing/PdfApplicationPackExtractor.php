<?php

namespace App\Infrastructure\ApplicationProcessing;

use App\Contracts\ApplicationProcessing\ApplicationPackExtractor;
use App\Exceptions\ApplicationProcessingException;
use Smalot\PdfParser\Parser;
use Throwable;

class PdfApplicationPackExtractor implements ApplicationPackExtractor
{
    public function __construct(
        private Parser $parser
    ) {
    }

    public function extract(string $path): string
    {
        if (!is_file($path)) {
            throw new ApplicationProcessingException(
                "Application pack file not found: {$path}"
            );
        }

        try {
            $text = $this->parser
                ->parseFile($path)
                ->getText();
        } catch (Throwable $exception) {
            throw new ApplicationProcessingException(
                'Failed to extract application pack PDF.'
            );
        }

        $text = trim($text);

        if ($text === '') {
            throw new ApplicationProcessingException(
                'Application pack PDF contains no extractable text.'
            );
        }

        return $text;
    }
}