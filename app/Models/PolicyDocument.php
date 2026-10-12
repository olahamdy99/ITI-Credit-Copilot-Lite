<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyDocument extends Model
{
    protected $fillable = [
        'policy_edition_id',
        'supersedes_document_id',
        'type',
        'title',
        'source_file',
        'format',
        'effective_from',
        'effective_to',
        'processing_status',
        'failure_reason',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function policyEdition(): BelongsTo
    {
        return $this->belongsTo(PolicyEdition::class);
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(
            PolicyDocument::class,
            'supersedes_document_id'
        );
    }

    public function supersededBy(): HasMany
    {
        return $this->hasMany(
            PolicyDocument::class,
            'supersedes_document_id'
        );
    }
    public function chunks(): HasMany
    {
        return $this->hasMany(PolicyChunk::class);
    }
}