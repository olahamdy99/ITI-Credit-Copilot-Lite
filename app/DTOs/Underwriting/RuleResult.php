<?php

namespace App\DTOs\Underwriting;

class RuleResult
{
    public function __construct(
        public readonly string $rule,
        public readonly string $status,
        public readonly string $message,
        public readonly array $details = [],
    ) {
    }
}