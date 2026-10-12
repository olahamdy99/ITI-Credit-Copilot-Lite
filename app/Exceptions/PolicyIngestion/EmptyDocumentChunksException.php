<?php

namespace App\Exceptions\PolicyIngestion;

use RuntimeException;

class EmptyDocumentChunksException extends RuntimeException
{
    public function __construct(string $documentTitle)
    {
        parent::__construct(
            "No chunks were generated for policy document: {$documentTitle}"
        );
    }
}