<?php

namespace App\Services\PolicyIngestion\Extractors;

use App\Contracts\PolicyIngestion\DocumentExtractor;
use App\Exceptions\PolicyIngestion\DocumentNotFoundException;
use App\Exceptions\PolicyIngestion\DocumentReadException;
use Smalot\PdfParser\Parser;
use Throwable;

class PdfExtractor implements DocumentExtractor
{
    public function supports(string $format): bool
    {
        return strtolower($format) === 'pdf';
    }

    public function extract(string $path): string
    {
        if (! file_exists($path)) {
            throw new DocumentNotFoundException($path);
        }

        try {
            $parser = new Parser;

            $pdf = $parser->parseFile($path);

            $text = trim($pdf->getText());

            if ($text === '') {
                throw new DocumentReadException($path);
            }

            return $text;
        } catch (DocumentReadException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new DocumentReadException($path);
        }
    }
}
