<?php

namespace App\Services\Policy;

use App\Exceptions\Policy\PolicyEditionNotFoundException;
use App\Models\PolicyEdition;
use DateTimeInterface;

class PolicyEditionSelector
{
    public function select(DateTimeInterface $applicationDate): PolicyEdition
    {
        $date = $applicationDate->format('Y-m-d');

        $edition = PolicyEdition::query()
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->where('status', 'active')
            ->orderByDesc('effective_from')
            ->first();

        if (!$edition) {
            throw new PolicyEditionNotFoundException();
        }

        return $edition;
    }
}