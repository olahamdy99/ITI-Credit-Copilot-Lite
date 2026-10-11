<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'loan_application_id',
        'type',
        'source_file',
        'original_name',
        'mime_type',
        'size',
        'processing_status',
        'failure_reason',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }
}