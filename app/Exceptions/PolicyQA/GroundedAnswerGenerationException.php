<?php

namespace App\Exceptions\PolicyQA;

use RuntimeException;

class GroundedAnswerGenerationException extends RuntimeException
{
    public function __construct(
        string $message = 'Failed to generate grounded policy answer.'
    ) {
        parent::__construct($message);
    }
}
