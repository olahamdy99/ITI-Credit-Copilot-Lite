<?php

namespace App\DTOs\PolicyRetrieval;

class PolicySearchResult
{
    public function __construct(
        public readonly int $chunkId,
        public readonly int $policyDocumentId,
        public readonly ?int $policyEditionId,
        public readonly string $documentTitle,
        public readonly string $documentType,
        public readonly string $sourceFile,
        public readonly ?string $sectionId,
        public readonly ?string $sectionTitle,
        public readonly ?string $clauseId,
        public readonly ?int $pageNumber,
        public readonly string $content,
        public readonly float $similarity,
    ) {
    }

    public function citation(): array
    {
        return [
            'document' => $this->documentTitle,
            'source_file' => $this->sourceFile,
            'section_id' => $this->sectionId,
            'clause_id' => $this->clauseId,
            'page_number' => $this->pageNumber,
        ];
    }
}