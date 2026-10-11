<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationExtraction extends Model
{
    protected $fillable = [
        'loan_application_id',
        'net_monthly_income',
        'existing_monthly_obligations',
        'employment_start_date',
        'bureau_score',
        'evidence',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'net_monthly_income' => 'decimal:2',
        'existing_monthly_obligations' => 'decimal:2',
        'employment_start_date' => 'date',
        'bureau_score' => 'integer',
        'evidence' => 'array',
    ];

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }
}