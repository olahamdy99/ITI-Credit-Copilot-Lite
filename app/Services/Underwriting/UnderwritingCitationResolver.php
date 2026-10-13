<?php

namespace App\Services\Underwriting;

use App\Models\PolicyChunk;
use App\Models\PolicyEdition;
use RuntimeException;

class UnderwritingCitationResolver
{
    public function resolve(
        string $ruleCode,
        PolicyEdition $edition
    ): string {
        return match ($ruleCode) {
            'dbr' => $this->resolvePolicyClause(
                edition: $edition,
                sectionId: 'CP-4',
                clauseId: 'CP-4.1',
            ),

            'minimum_income' => $this->resolvePolicyClause(
                edition: $edition,
                sectionId: 'CP-3',
                clauseId: 'CP-3.3',
            ),

            'employment_duration' => $this->resolvePolicyClause(
                edition: $edition,
                sectionId: 'CP-3',
                clauseId: 'CP-3.2',
            ),

            'bureau_score' => $this->resolvePolicyClause(
                edition: $edition,
                sectionId: 'CP-3',
                clauseId: 'CP-3.6',
            ),

            'age_at_maturity' => $this->resolvePolicyClause(
                edition: $edition,
                sectionId: 'CP-3',
                clauseId: 'CP-3.5',
            ),

            'tenor',
            'amount' => $this->resolveProductSection('PS-3'),

            default => throw new RuntimeException(
                "No policy citation mapping exists for rule [{$ruleCode}]."
            ),
        };
    }

    private function resolvePolicyClause(
        PolicyEdition $edition,
        string $sectionId,
        string $clauseId
    ): string {
        $chunk = PolicyChunk::query()
            ->with('policyDocument')
            ->where('policy_edition_id', $edition->id)
            ->where('section_id', $sectionId)
            ->where('clause_id', $clauseId)
            ->first();

        if (! $chunk || ! $chunk->policyDocument) {
            throw new RuntimeException(
                "Policy citation not found for [{$clauseId}] in edition [{$edition->code}]."
            );
        }

        return sprintf(
            '%s — %s',
            $chunk->policyDocument->title,
            $clauseId
        );
    }

    private function resolveProductSection(
        string $sectionId
    ): string {
        $chunk = PolicyChunk::query()
            ->with('policyDocument')
            ->where('section_id', $sectionId)
            ->whereHas(
                'policyDocument',
                fn ($query) => $query->where(
                    'title',
                    'Personal Loan Product Sheet'
                )
            )
            ->first();

        if (! $chunk || ! $chunk->policyDocument) {
            throw new RuntimeException(
                "Product citation not found for section [{$sectionId}]."
            );
        }

        return sprintf(
            '%s — %s',
            $chunk->policyDocument->title,
            $sectionId
        );
    }

    public function resolveClause(
        PolicyEdition $edition,
        string $sectionId,
        string $clauseId
    ): string {
        return $this->resolvePolicyClause(
            edition: $edition,
            sectionId: $sectionId,
            clauseId: $clauseId,
        );
    }

    public function resolveProduct(
        string $sectionId
    ): string {
        return $this->resolveProductSection(
            $sectionId
        );
    }
}
