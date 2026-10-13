<?php

namespace App\Console\Commands;

use App\Models\PolicyDocument;
use App\Models\PolicyEdition;
use Illuminate\Console\Command;

class ImportDemoCorpus extends Command
{
    protected $signature = 'demo:import-corpus';

    protected $description = 'Import the provided synthetic demo corpus into the database';

    public function handle(): int
    {
        $this->info('Importing demo corpus...');

        $policy2024 = PolicyEdition::updateOrCreate(
            ['code' => 'CP-2024'],
            [
                'name' => 'Credit Policy 2024',
                'effective_from' => '2024-01-01',
                'effective_to' => '2025-02-28',
                'status' => 'active',
            ]
        );

        $policy2025 = PolicyEdition::updateOrCreate(
            ['code' => 'CP-2025'],
            [
                'name' => 'Credit Policy 2025',
                'effective_from' => '2025-03-01',
                'effective_to' => null,
                'status' => 'active',
            ]
        );

        $creditPolicy2024 = PolicyDocument::updateOrCreate(
            [
                'type' => 'credit_policy',
                'title' => 'Credit Policy 2024',
            ],
            [
                'policy_edition_id' => $policy2024->id,
                'supersedes_document_id' => null,
                'source_file' => 'corpus/policy/credit-policy-2024.pdf',
                'format' => 'pdf',
                'effective_from' => '2024-01-01',
                'effective_to' => '2025-02-28',
                'processing_status' => 'pending',
                'failure_reason' => null,
            ]
        );

        PolicyDocument::updateOrCreate(
            [
                'type' => 'credit_policy',
                'title' => 'Credit Policy 2025',
            ],
            [
                'policy_edition_id' => $policy2025->id,
                'supersedes_document_id' => $creditPolicy2024->id,
                'source_file' => 'corpus/policy/credit-policy-2025.pdf',
                'format' => 'pdf',
                'effective_from' => '2025-03-01',
                'effective_to' => null,
                'processing_status' => 'pending',
                'failure_reason' => null,
            ]
        );

        PolicyDocument::updateOrCreate(
            [
                'type' => 'procedures_manual',
                'title' => 'Credit Procedures Manual',
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => null,
                'source_file' => 'corpus/policy/credit-procedures-manual.pdf',
                'format' => 'pdf',
                'effective_from' => '2025-03-01',
                'effective_to' => null,
                'processing_status' => 'pending',
                'failure_reason' => null,
            ]
        );

        PolicyDocument::updateOrCreate(
            [
                'type' => 'product_sheet',
                'title' => 'Personal Loan Product Sheet',
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => null,
                'source_file' => 'corpus/policy/product-sheet-personal-loan.md',
                'format' => 'markdown',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'processing_status' => 'pending',
                'failure_reason' => null,
            ]
        );

        PolicyDocument::updateOrCreate(
            [
                'type' => 'pricing_table',
                'title' => 'Pricing Table',
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => null,
                'source_file' => 'corpus/policy/pricing-table.csv',
                'format' => 'csv',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
                'processing_status' => 'pending',
                'failure_reason' => null,
            ]
        );

        $circular2024 = PolicyDocument::updateOrCreate(
            [
                'type' => 'central_bank_circular',
                'title' => 'Central Bank Circular 2024-07',
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => null,
                'source_file' => 'corpus/policy/circular-2024-07.md',
                'format' => 'markdown',
                'effective_from' => '2024-08-01',
                'effective_to' => '2025-02-28',
                'processing_status' => 'pending',
                'failure_reason' => null,
            ]
        );

        PolicyDocument::updateOrCreate(
            [
                'type' => 'central_bank_circular',
                'title' => 'Central Bank Circular 2025-02',
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => $circular2024->id,
                'source_file' => 'corpus/policy/circular-2025-02.md',
                'format' => 'markdown',
                'effective_from' => '2025-03-01',
                'effective_to' => null,
                'processing_status' => 'pending',
                'failure_reason' => null,
            ]
        );

        $this->info('Policy corpus imported successfully.');

        return self::SUCCESS;
    }
}
