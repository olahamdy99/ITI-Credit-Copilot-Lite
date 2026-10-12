<?php

namespace App\Exceptions\PolicyIngestion;

use RuntimeException;

class DocumentReadException extends RuntimeException
{
    public function __construct(string $path)
    {
        parent::__construct(
            "Unable to read policy document: {$path}"
        );
    }
}