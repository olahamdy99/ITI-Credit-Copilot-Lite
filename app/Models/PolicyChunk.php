<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_document_id',
        'policy_edition_id',
        'chunk_index',
        'section_id',
        'section_title',
        'clause_id',
        'page_number',
        'content',
        'effective_from',
        'effective_to',
        'metadata',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'page_number' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'metadata' => 'array',
    ];

    public function policyDocument(): BelongsTo
    {
        return $this->belongsTo(PolicyDocument::class);
    }

    public function policyEdition(): BelongsTo
    {
        return $this->belongsTo(PolicyEdition::class);
    }
}