<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assessment extends Model
{
    protected $fillable = [
        'loan_application_id',
        'policy_edition_id',
        'annual_rate_percent',
        'monthly_installment',
        'debt_burden_ratio',
        'maximum_eligible_amount',
        'recommendation',
        'recommended_amount',
        'approval_required_from',
        'status',
        'run_id',
    ];

    protected $casts = [
        'annual_rate_percent' => 'decimal:4',
        'monthly_installment' => 'decimal:2',
        'debt_burden_ratio' => 'decimal:6',
        'maximum_eligible_amount' => 'decimal:2',
        'recommended_amount' => 'decimal:2',
    ];

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function policyEdition(): BelongsTo
    {
        return $this->belongsTo(PolicyEdition::class);
    }

    public function ruleResults(): HasMany
    {
        return $this->hasMany(AssessmentRuleResult::class);
    }

    public function creditMemo(): HasOne
    {
        return $this->hasOne(CreditMemo::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }
}