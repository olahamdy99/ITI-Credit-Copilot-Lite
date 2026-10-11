<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    protected $fillable = [
        'assessment_id',
        'approved_by',
        'decision',
        'comment',
        'decided_at',
        'authority_limit',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
        'authority_limit' => 'decimal:2',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }
}