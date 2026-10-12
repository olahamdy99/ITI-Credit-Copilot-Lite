<?php

namespace App\Services\PolicyIngestion\Extractors;

use App\Contracts\PolicyIngestion\DocumentExtractor;
use App\Exceptions\PolicyIngestion\DocumentNotFoundException;
use App\Exceptions\PolicyIngestion\DocumentReadException;

class CsvExtractor implements DocumentExtractor
{
    public function supports(string $format): bool
    {
        return strtolower($format) === 'csv';
    }

    public function extract(string $path): string
    {
        if (! file_exists($path)) {
            throw new DocumentNotFoundException($path);
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new DocumentReadException($path);
        }

        $rows = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = implode(
                    ' | ',
                    array_map(
                        fn ($value) => trim((string) $value),
                        $row
                    )
                );
            }
        } finally {
            fclose($handle);
        }

        return trim(implode(PHP_EOL, $rows));
    }
}