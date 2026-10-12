<?php

namespace App\Services\PolicyIngestion\Chunkers;

use App\Contracts\PolicyIngestion\DocumentChunker;

class CsvRowChunker implements DocumentChunker
{
    public function supports(string $format): bool
    {
        return strtolower($format) === 'csv';
    }

    public function chunk(string $text): array
    {
        $lines = array_values(
            array_filter(
                preg_split('/\R/', $text),
                fn ($line) => trim($line) !== ''
            )
        );

        if (count($lines) <= 1) {
            return [];
        }

        $header = array_shift($lines);

        $chunks = [];

        foreach ($lines as $index => $line) {
            $chunks[] = [
                'chunk_index' => $index,
                'section_id' => 'PRICING',
                'section_title' => 'Pricing Table',
                'clause_id' => null,
                'content' => $header . PHP_EOL . $line,
            ];
        }

        return $chunks;
    }
}