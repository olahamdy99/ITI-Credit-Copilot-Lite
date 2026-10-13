<?php

namespace App\Infrastructure\PolicyRetrieval;

use App\Contracts\PolicyRetrieval\PolicySearchRepository;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use App\DTOs\PolicyRetrieval\PolicySearchResult;

class PgVectorPolicySearchRepository implements PolicySearchRepository
{
    public function search(
        array $embedding,
        DateTimeInterface $applicationDate,
        ?int $policyEditionId = null,
        int $limit = 5
    ): array {
        $vector = '[' . implode(',', $embedding) . ']';

        $date = $applicationDate->format('Y-m-d');

        $query = DB::table('policy_chunks as pc')
            ->join(
                'policy_documents as pd',
                'pd.id',
                '=',
                'pc.policy_document_id'
            )
            ->whereNotNull('pc.embedding')
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('pd.effective_from')
                    ->orWhere('pd.effective_from', '<=', $date);
            })
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('pd.effective_to')
                    ->orWhere('pd.effective_to', '>=', $date);
            });

        if ($policyEditionId !== null) {
            $query->where(function ($query) use ($policyEditionId) {
                $query
                    ->where('pd.type', '!=', 'credit_policy')
                    ->orWhere(
                        'pc.policy_edition_id',
                        '=',
                        $policyEditionId
                    );
            });
        }

        return $query
            ->select([
                'pc.id',
                'pc.policy_document_id',
                'pc.policy_edition_id',
                'pd.title as document_title',
                'pd.type as document_type',
                'pd.source_file',
                'pc.section_id',
                'pc.section_title',
                'pc.clause_id',
                'pc.page_number',
                'pc.content',
                'pc.effective_from',
                'pc.effective_to',
            ])
            ->selectRaw(
                '1 - (pc.embedding <=> ?::vector) AS similarity',
                [$vector]
            )
            ->orderByRaw(
                'pc.embedding <=> ?::vector',
                [$vector]
            )
            ->limit($limit)
            ->get()
            ->map(
                fn ($row) => new PolicySearchResult(
                    chunkId: $row->id,
                    policyDocumentId: $row->policy_document_id,
                    policyEditionId: $row->policy_edition_id,
                    documentTitle: $row->document_title,
                    documentType: $row->document_type,
                    sourceFile: $row->source_file,
                    sectionId: $row->section_id,
                    sectionTitle: $row->section_title,
                    clauseId: $row->clause_id,
                    pageNumber: $row->page_number,
                    content: $row->content,
                    similarity: (float) $row->similarity,
                )
            )
            ->all();
    }
}