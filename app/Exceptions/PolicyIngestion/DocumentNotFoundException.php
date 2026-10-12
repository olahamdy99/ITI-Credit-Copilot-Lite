<?php

namespace App\Exceptions\PolicyIngestion;

use RuntimeException;

class DocumentNotFoundException extends RuntimeException
{
    public function __construct(string $path)
    {
        parent::__construct(
            "Policy document not found: {$path}"
        );
    }
}