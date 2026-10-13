<?php

namespace App\Services\ApplicationProcessing;

use App\Exceptions\ApplicationProcessingException;

class PricingSegmentResolver
{
    public function resolve(string $text): string
    {
        $matched = preg_match(
            '/Salary\s+transferred\s+to\s+Delta\s*[:\-]?\s*(Yes|No)\b/i',
            $text,
            $matches
        );

        if ($matched !== 1) {
            throw new ApplicationProcessingException(
                'Could not determine pricing segment from application.'
            );
        }

        return strtolower($matches[1]) === 'yes'
            ? 'payroll_transfer'
            : 'standard';
    }
}