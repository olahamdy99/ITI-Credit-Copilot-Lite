<?php

namespace App\Contracts\PolicyRetrieval;

use DateTimeInterface;

interface PolicySearchRepository
{
    public function search(
        array $embedding,
        DateTimeInterface $applicationDate,
        ?int $policyEditionId = null,
        int $limit = 5
    ): array;
}