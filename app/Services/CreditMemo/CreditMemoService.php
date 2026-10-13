<?php

namespace App\Services\CreditMemo;

use App\Contracts\LLM\LanguageModelProvider;
use App\Models\Assessment;
use App\Models\CreditMemo;
use App\Services\Underwriting\UnderwritingCitationResolver;
use RuntimeException;

class CreditMemoService
{
    public function __construct(
        private LanguageModelProvider $languageModelProvider,
        private UnderwritingCitationResolver $citationResolver
    ) {}

    public function generate(Assessment $assessment): CreditMemo
    {
        $assessment->loadMissing([
            'loanApplication.extraction',
            'policyEdition',
            'ruleResults',
        ]);

        if ($assessment->ruleResults->isEmpty()) {
            throw new RuntimeException(
                'Assessment has no underwriting rule results.'
            );
        }

        if (
            $assessment->ruleResults->contains(
                fn ($rule) => empty($rule->citation)
            )
        ) {
            throw new RuntimeException(
                'Assessment contains rule results without policy citations.'
            );
        }

        $systemPrompt = $this->loadSystemPrompt();

        $tokens = $this->buildTokens($assessment);

        // $userPrompt = $this->buildUserPrompt(
        //     assessment: $assessment,
        //     tokens: $tokens
        // );
        $userPrompt = $this->buildUserPrompt(
            assessment: $assessment
        );

        $draft = $this->languageModelProvider->generate(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt
        );

        $this->ensureDraftContainsNoGeneratedNumbers(
            $draft
        );
        $summary = str_replace(
            array_keys($tokens),
            array_values($tokens),
            $draft
        );

        $this->ensureNoUnresolvedTokens($summary);

        return CreditMemo::updateOrCreate(
            [
                'assessment_id' => $assessment->id,
            ],
            [
                'summary' => $summary,
                'citations' => collect(
                    $assessment->ruleResults
                        ->pluck('citation')
                        ->all()
                )
                    ->push(
                        $this->citationResolver->resolveClause(
                            $assessment->policyEdition,
                            'CP-4',
                            'CP-4.2'
                        ),

                        $this->citationResolver->resolveClause(
                            $assessment->policyEdition,
                            'CP-4',
                            'CP-4.3'
                        ),
                        'Pricing Table — PRICING',
                        $this->citationResolver->resolveProduct(
                            'PS-4'
                        ),
                    )
                    ->unique()
                    ->values()
                    ->all(),
                'status' => 'draft',
            ]
        );
    }

    private function loadSystemPrompt(): string
    {
        $path = resource_path(
            'prompts/credit_memo_system.txt'
        );

        if (! is_file($path)) {
            throw new RuntimeException(
                'Credit memo system prompt file was not found.'
            );
        }

        return trim(
            file_get_contents($path)
        );
    }

    private function buildTokens(
        Assessment $assessment
    ): array {
        $application = $assessment->loanApplication;
        $extraction = $application->extraction;

        return [
            '{{REFERENCE}}' => $application->reference_code,
            '{{REQUESTED_AMOUNT}}' => number_format(
                (float) $application->requested_amount,
                2,
                '.',
                ''
            ),
            '{{TENOR_MONTHS}}' => (string) $application->requested_tenor_months,
            '{{NET_MONTHLY_INCOME}}' => number_format(
                (float) $extraction->net_monthly_income,
                2,
                '.',
                ''
            ),
            '{{MONTHLY_OBLIGATIONS}}' => number_format(
                (float) $extraction->existing_monthly_obligations,
                2,
                '.',
                ''
            ),
            '{{BUREAU_SCORE}}' => (string) $extraction->bureau_score,
            '{{ANNUAL_RATE}}' => number_format(
                (float) $assessment->annual_rate_percent,
                2,
                '.',
                ''
            ),
            '{{MONTHLY_INSTALLMENT}}' => number_format(
                (float) $assessment->monthly_installment,
                2,
                '.',
                ''
            ),
            '{{DBR}}' => number_format(
                (float) $assessment->debt_burden_ratio,
                2,
                '.',
                ''
            ),
            '{{MAX_ELIGIBLE_AMOUNT}}' => number_format(
                (float) $assessment->maximum_eligible_amount,
                2,
                '.',
                ''
            ),
            '{{RECOMMENDED_AMOUNT}}' => number_format(
                (float) $assessment->recommended_amount,
                2,
                '.',
                ''
            ),
            '{{RECOMMENDATION}}' => $assessment->recommendation,
            '{{POLICY_EDITION}}' => $assessment->policyEdition->code,

            '{{CITATION_DBR}}' => $this->citationFor(
                $assessment,
                'dbr'
            ),
            '{{CITATION_MINIMUM_INCOME}}' => $this->citationFor(
                $assessment,
                'minimum_income'
            ),
            '{{CITATION_EMPLOYMENT}}' => $this->citationFor(
                $assessment,
                'employment_duration'
            ),
            '{{CITATION_BUREAU}}' => $this->citationFor(
                $assessment,
                'bureau_score'
            ),
            '{{CITATION_AGE}}' => $this->citationFor(
                $assessment,
                'age_at_maturity'
            ),
            '{{CITATION_TENOR}}' => $this->citationFor(
                $assessment,
                'tenor'
            ),
            '{{CITATION_AMOUNT}}' => $this->citationFor(
                $assessment,
                'amount'
            ),
            '{{CITATION_INSTALLMENT}}' => $this->citationResolver->resolveClause(
                edition: $assessment->policyEdition,
                sectionId: 'CP-4',
                clauseId: 'CP-4.2',
            ),

            '{{CITATION_MAX_ELIGIBLE}}' => $this->citationResolver->resolveClause(
                edition: $assessment->policyEdition,
                sectionId: 'CP-4',
                clauseId: 'CP-4.3',
            ),
            '{{CITATION_PRICING}}' => 'Pricing Table — PRICING',

            '{{CITATION_PRODUCT_PRICING}}' => $this->citationResolver->resolveProduct(
                'PS-4'
            ),

        ];
    }

    private function buildUserPrompt(
        Assessment $assessment
    ): string {
        $citationTokens = [
            'dbr' => '{{CITATION_DBR}}',
            'minimum_income' => '{{CITATION_MINIMUM_INCOME}}',
            'employment_duration' => '{{CITATION_EMPLOYMENT}}',
            'bureau_score' => '{{CITATION_BUREAU}}',
            'age_at_maturity' => '{{CITATION_AGE}}',
            'tenor' => '{{CITATION_TENOR}}',
            'amount' => '{{CITATION_AMOUNT}}',
        ];

        $rules = $assessment->ruleResults
            ->map(function ($rule) use ($citationTokens) {
                $citationToken = $citationTokens[$rule->rule_code]
                    ?? throw new RuntimeException(
                        "No citation placeholder exists for rule [{$rule->rule_code}]."
                    );

                return sprintf(
                    '- %s: %s. %s [%s]',
                    $rule->rule_name,
                    strtoupper($rule->result),
                    $rule->reason,
                    $citationToken
                );
            })
            ->implode("\n");

        return <<<PROMPT
    Draft a short underwriting credit memo.

    Use the supplied placeholders exactly.
    Do not replace placeholders with your own values.
    Do not write any digits yourself.

    Application reference: {{REFERENCE}}
    Policy edition: {{POLICY_EDITION}}
    Requested amount: {{REQUESTED_AMOUNT}}
    Requested tenor months: {{TENOR_MONTHS}}
    Net monthly income: {{NET_MONTHLY_INCOME}}
    Existing monthly obligations: {{MONTHLY_OBLIGATIONS}}
    Bureau score: {{BUREAU_SCORE}}

    Deterministic assessment:
    Annual rate: {{ANNUAL_RATE}} [{{CITATION_PRICING}}; {{CITATION_PRODUCT_PRICING}}]
    Monthly installment: {{MONTHLY_INSTALLMENT}} [{{CITATION_INSTALLMENT}}]
    DBR percentage: {{DBR}} [{{CITATION_DBR}}]
    Maximum eligible amount: {{MAX_ELIGIBLE_AMOUNT}} [{{CITATION_MAX_ELIGIBLE}}]
    Recommendation: {{RECOMMENDATION}}
    Recommended amount: {{RECOMMENDED_AMOUNT}}

    Rule outcomes:
    {$rules}

    Write the memo using these headings without numbering:
    Application Summary
    Affordability and Eligibility Assessment
    Policy-Grounded Recommendation
    Human Approval Requirement

    Under Human Approval Requirement, explicitly state that the assessment
    remains pending human approval and that no loan offer may be issued
    until an authorized credit officer approves it within their authority.

    Do not introduce any numerical value.
    Do not invent or modify policy citations.
    PROMPT;
    }

    private function ensureNoUnresolvedTokens(
        string $summary
    ): void {
        if (preg_match('/\{\{[A-Z0-9_]+\}\}/', $summary)) {
            throw new RuntimeException(
                'Credit memo contains unresolved placeholders.'
            );
        }
    }

    private function citationFor(
        Assessment $assessment,
        string $ruleCode
    ): string {
        $rule = $assessment->ruleResults->firstWhere(
            'rule_code',
            $ruleCode
        );

        if (! $rule || ! $rule->citation) {
            throw new RuntimeException(
                "Citation is missing for rule [{$ruleCode}]."
            );
        }

        return $rule->citation;
    }

    private function ensureDraftContainsNoGeneratedNumbers(
        string $draft
    ): void {
        $withoutPlaceholders = preg_replace(
            '/\{\{[A-Z0-9_]+\}\}/',
            '',
            $draft
        );

        if (preg_match('/\d/', $withoutPlaceholders)) {
            throw new RuntimeException(
                'Credit memo draft contains a number generated by the language model.'
            );
        }
    }
}
