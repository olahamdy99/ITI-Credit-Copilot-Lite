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
                'effective_to' => '2024-12-31',
                'status' => 'active',
            ]
        );

        $policy2025 = PolicyEdition::updateOrCreate(
            ['code' => 'CP-2025'],
            [
                'name' => 'Credit Policy 2025',
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'status' => 'active',
            ]
        );

        $documents = [
            [
                'policy_edition_id' => $policy2024->id,
                'supersedes_document_id' => null,
                'type' => 'credit_policy',
                'title' => 'Credit Policy 2024',
                'source_file' => 'corpus/policy/credit-policy-2024.pdf',
                'format' => 'pdf',
                'effective_from' => '2024-01-01',
                'effective_to' => '2024-12-31',
            ],
            [
                'policy_edition_id' => $policy2025->id,
                'supersedes_document_id' => null,
                'type' => 'credit_policy',
                'title' => 'Credit Policy 2025',
                'source_file' => 'corpus/policy/credit-policy-2025.pdf',
                'format' => 'pdf',
                'effective_from' => '2025-01-01',
                'effective_to' => null,
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => null,
                'type' => 'procedures_manual',
                'title' => 'Credit Procedures Manual',
                'source_file' => 'corpus/policy/credit-procedures-manual.pdf',
                'format' => 'pdf',
                'effective_from' => null,
                'effective_to' => null,
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => null,
                'type' => 'product_sheet',
                'title' => 'Personal Loan Product Sheet',
                'source_file' => 'corpus/policy/product-sheet-personal-loan.md',
                'format' => 'markdown',
                'effective_from' => '2024-01-01',
                'effective_to' => null,
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => null,
                'type' => 'pricing_table',
                'title' => 'Pricing Table',
                'source_file' => 'corpus/policy/pricing-table.csv',
                'format' => 'csv',
                'effective_from' => null,
                'effective_to' => null,
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => null,
                'type' => 'central_bank_circular',
                'title' => 'Central Bank Circular 2024-07',
                'source_file' => 'corpus/policy/circular-2024-07.md',
                'format' => 'markdown',
                'effective_from' => '2024-07-01',
                'effective_to' => null,
            ],
            [
                'policy_edition_id' => null,
                'supersedes_document_id' => null,
                'type' => 'central_bank_circular',
                'title' => 'Central Bank Circular 2025-02',
                'source_file' => 'corpus/policy/circular-2025-02.md',
                'format' => 'markdown',
                'effective_from' => '2025-02-01',
                'effective_to' => null,
            ],
        ];

        foreach ($documents as $document) {
            PolicyDocument::updateOrCreate(
                [
                    // 'source_file' => $document['source_file'],
                    'type' => $document['type'],
                    'title' => $document['title'],
                ],
                array_merge(
                    $document,
                    [
                        'processing_status' => 'pending',
                        'failure_reason' => null,
                    ]
                )
            );
        }

        $this->info('Policy corpus imported successfully.');

        return self::SUCCESS;
    }
}