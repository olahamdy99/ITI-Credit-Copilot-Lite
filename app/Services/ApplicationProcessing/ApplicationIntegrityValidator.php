<?php

namespace App\Services\ApplicationProcessing;

use App\Exceptions\ApplicationIntegrityException;

class ApplicationIntegrityValidator
{
    private const SUSPICIOUS_PATTERNS = [
        '/ignore\s+all\s+previous\s+instructions/i',
        '/ignore\s+previous\s+instructions/i',
        '/system\s+note/i',
        '/automated\s+reviewer/i',
        '/approve\s+this\s+application/i',
        '/do\s+not\s+mention/i',
        '/all\s+eligibility\s+checks\s+have\s+already\s+been\s+completed/i',
        '/tampered\s+document/i',
    ];

    public function validate(string $rawText): void
    {
        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $rawText) === 1) {
                throw new ApplicationIntegrityException(
                    'Application document contains suspicious or untrusted instructions and requires human review.'
                );
            }
        }
    }
}