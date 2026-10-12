<?php

namespace App\Exceptions\PolicyIngestion;

use RuntimeException;

class UnsupportedChunkFormatException extends RuntimeException
{
    public function __construct(string $format)
    {
        parent::__construct(
            "No policy chunker available for format: {$format}"
        );
    }
}