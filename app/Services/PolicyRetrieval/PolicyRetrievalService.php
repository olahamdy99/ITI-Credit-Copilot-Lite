<?php

namespace App\Services\PolicyRetrieval;

use App\Contracts\Embeddings\EmbeddingProvider;
use App\Contracts\PolicyRetrieval\PolicySearchRepository;
use App\Exceptions\PolicyRetrieval\InsufficientPolicyEvidenceException;
use DateTimeInterface;

class PolicyRetrievalService
{
    private const MIN_SIMILARITY = 0.73;

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

        $results = $this->repository->search(
            embedding: $embedding,
            applicationDate: $applicationDate,
            policyEditionId: $policyEditionId,
            limit: $limit,
        );

        $qualifiedResults = array_values(
            array_filter(
                $results,
                fn ($result) =>
                    $result->similarity >= self::MIN_SIMILARITY
            )
        );

        if ($qualifiedResults === []) {
            throw new InsufficientPolicyEvidenceException();
        }

        return $qualifiedResults;
    }
}