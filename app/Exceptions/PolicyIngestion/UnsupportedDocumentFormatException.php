<?php

namespace App\Exceptions\PolicyIngestion;

use RuntimeException;

class UnsupportedDocumentFormatException extends RuntimeException
{
    public function __construct(string $format)
    {
        parent::__construct(
            "Unsupported policy document format: {$format}"
        );
    }
}