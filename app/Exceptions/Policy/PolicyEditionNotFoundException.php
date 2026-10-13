<?php

namespace App\Exceptions\Policy;

use RuntimeException;

class PolicyEditionNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'No policy edition is valid for the supplied application date.'
        );
    }
}
