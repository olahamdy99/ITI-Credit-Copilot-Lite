<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRuleResult extends Model
{
    protected $fillable = [
        'assessment_id',
        'rule_code',
        'rule_name',
        'result',
        'reason',
        'citation',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }
}