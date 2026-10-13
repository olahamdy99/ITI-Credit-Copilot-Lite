<?php

namespace App\DTOs\PolicyQA;

class GroundedAnswer
{
    public function __construct(
        public readonly string $answer,
        public readonly array $citations,
    ) {}
}
