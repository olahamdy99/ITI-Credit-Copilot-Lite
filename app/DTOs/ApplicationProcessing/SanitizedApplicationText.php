<?php

namespace App\DTOs\ApplicationProcessing;

class SanitizedApplicationText
{
    public function __construct(
        public readonly string $text,
        public readonly array $removedFields,
        public readonly array $maskedFields,
    ) {}
}
