<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanApplication extends Model
{
    protected $fillable = [
        'reference_code',
        'requested_amount',
        'requested_tenor_months',
        'date_of_birth',
        'application_date',
        'status',
        'policy_edition_id',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'requested_tenor_months' => 'integer',
        'date_of_birth' => 'date',
        'application_date' => 'date',
    ];

    public function policyEdition(): BelongsTo
    {
        return $this->belongsTo(PolicyEdition::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function extraction(): HasOne
    {
        return $this->hasOne(ApplicationExtraction::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
