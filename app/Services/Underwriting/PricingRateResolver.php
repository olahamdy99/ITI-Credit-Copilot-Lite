<?php

namespace App\Services\Underwriting;

use App\Models\PolicyChunk;
use RuntimeException;

class PricingRateResolver
{
    public function resolve(
        int $tenorMonths,
        string $segment,
        string $applicationDate
    ): float {
        $chunks = PolicyChunk::query()
            ->where('section_id', 'PRICING')
            ->whereHas('policyDocument', function ($query) use ($applicationDate) {
                $query
                    ->where('title', 'Pricing Table')
                    ->where('processing_status', 'completed')
                    ->whereDate('effective_from', '<=', $applicationDate)
                    ->where(function ($query) use ($applicationDate) {
                        $query
                            ->whereNull('effective_to')
                            ->orWhereDate(
                                'effective_to',
                                '>=',
                                $applicationDate
                            );
                    });
            })
            ->get();

        foreach ($chunks as $chunk) {
            $row = $this->parseRow($chunk->content);

            if ($row === null) {
                continue;
            }

            if (
                $row['segment'] === $segment
                && $tenorMonths >= $row['tenor_from']
                && $tenorMonths <= $row['tenor_to']
            ) {
                return $row['annual_rate'];
            }
        }

        throw new RuntimeException(
            sprintf(
                'No pricing rate found for segment [%s] and tenor [%d].',
                $segment,
                $tenorMonths
            )
        );
    }

    private function parseRow(string $content): ?array
    {
        $lines = array_values(
            array_filter(
                array_map('trim', preg_split('/\R/', $content))
            )
        );

        if (count($lines) < 2) {
            return null;
        }

        $values = array_map(
            'trim',
            explode('|', $lines[count($lines) - 1])
        );

        if (count($values) < 7) {
            return null;
        }

        return [
            'tenor_from' => (int) $values[3],
            'tenor_to' => (int) $values[4],
            'segment' => $values[5],
            'annual_rate' => (float) $values[6],
        ];
    }
}