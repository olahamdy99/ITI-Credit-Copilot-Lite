<?php

namespace App\Services\PolicyRetrieval;

use App\Contracts\Embeddings\EmbeddingProvider;
use App\Contracts\PolicyRetrieval\PolicySearchRepository;
use DateTimeInterface;

class PolicyRetrievalService
{
    public function __construct(
        private EmbeddingProvider $embeddingProvider,
        private PolicySearchRepository $repository,
    ) {
    }

    public function search(
        string $query,
        DateTimeInterface $applicationDate,
        ?int $policyEditionId = null,
        int $limit = 5
    ): array {
        $embedding = $this->embeddingProvider->embed($query);

        return $this->repository->search(
            embedding: $embedding,
            applicationDate: $applicationDate,
            policyEditionId: $policyEditionId,
            limit: $limit,
        );
    }
}