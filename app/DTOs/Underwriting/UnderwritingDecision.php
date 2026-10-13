<?php

namespace App\DTOs\Underwriting;

class UnderwritingDecision
{
    public function __construct(
        public readonly string $decision,
        public readonly array $rules,
    ) {}
}
