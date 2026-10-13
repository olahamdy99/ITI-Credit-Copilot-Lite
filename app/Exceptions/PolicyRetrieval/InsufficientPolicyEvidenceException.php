<?php

namespace App\Exceptions\PolicyRetrieval;

use RuntimeException;

class InsufficientPolicyEvidenceException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Insufficient policy evidence to answer this question.'
        );
    }
}