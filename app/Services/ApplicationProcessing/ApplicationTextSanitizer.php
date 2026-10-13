<?php

namespace App\Services\ApplicationProcessing;

use App\DTOs\ApplicationProcessing\SanitizedApplicationText;

class ApplicationTextSanitizer
{
    public function sanitize(string $text): SanitizedApplicationText
    {
        $removedFields = [];
        $maskedFields = [];

        $text = $this->removeField(
            $text,
            'Gender',
            $removedFields
        );

        $text = $this->removeField(
            $text,
            'Marital status',
            $removedFields
        );

        $text = $this->removeField(
            $text,
            'Religion',
            $removedFields
        );

        $text = $this->removeField(
            $text,
            'Nationality',
            $removedFields
        );

        $text = $this->maskPattern(
            $text,
            '/\b\d{14}\b/',
            '[NATIONAL_ID_REDACTED]',
            'national_id',
            $maskedFields
        );

        $text = $this->maskPattern(
            $text,
            '/\b01[0-9][0-9\- ]{7,12}\b/',
            '[PHONE_REDACTED]',
            'phone',
            $maskedFields
        );

        $text = $this->maskApplicantName(
            $text,
            $maskedFields
        );

        return new SanitizedApplicationText(
            text: trim($text),
            removedFields: array_values(array_unique($removedFields)),
            maskedFields: array_values(array_unique($maskedFields)),
        );
    }

    private function removeField(
        string $text,
        string $field,
        array &$removedFields
    ): string {
        $pattern = '/^'.preg_quote($field, '/').'\s+.*$/mi';

        if (preg_match($pattern, $text)) {
            $removedFields[] = $field;
        }

        return preg_replace($pattern, '', $text) ?? $text;
    }

    private function maskPattern(
        string $text,
        string $pattern,
        string $replacement,
        string $field,
        array &$maskedFields
    ): string {
        if (preg_match($pattern, $text)) {
            $maskedFields[] = $field;
        }

        return preg_replace(
            $pattern,
            $replacement,
            $text
        ) ?? $text;
    }

    private function maskApplicantName(
        string $text,
        array &$maskedFields
    ): string {
        if (
            preg_match(
                '/^Full name\s+(.+)$/mi',
                $text,
                $matches
            ) !== 1
        ) {
            return $text;
        }

        $name = trim($matches[1]);

        if ($name === '') {
            return $text;
        }

        $maskedFields[] = 'full_name';

        return str_replace(
            $name,
            '[APPLICANT_NAME_REDACTED]',
            $text
        );
    }
}
