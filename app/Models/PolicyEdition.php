<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyEdition extends Model
{
    protected $fillable = [
        'code',
        'name',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(PolicyDocument::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
    public function chunks(): HasMany
    {
        return $this->hasMany(PolicyChunk::class);
    }
}