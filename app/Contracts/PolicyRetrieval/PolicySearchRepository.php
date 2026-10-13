<?php

namespace App\Contracts\PolicyRetrieval;

use DateTimeInterface;

interface PolicySearchRepository
{
    /**
     * @return array<\App\DTOs\PolicyRetrieval\PolicySearchResult>
     */
    public function search(
        array $embedding,
        DateTimeInterface $applicationDate,
        ?int $policyEditionId = null,
        int $limit = 5
    ): array;
}
