<?php

namespace App\Services\PolicyIngestion\Extractors;

use App\Contracts\PolicyIngestion\DocumentExtractor;
use App\Exceptions\PolicyIngestion\DocumentNotFoundException;
use App\Exceptions\PolicyIngestion\DocumentReadException;

class MarkdownExtractor implements DocumentExtractor
{
    public function supports(string $format): bool
    {
        return in_array(
            strtolower($format),
            ['markdown', 'md'],
            true
        );
    }

    public function extract(string $path): string
    {
        if (! file_exists($path)) {
            throw new DocumentNotFoundException($path);
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new DocumentReadException($path);
        }

        return trim($content);
    }
}
