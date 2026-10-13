<?php

namespace App\Services\ApplicationProcessing;

use App\DTOs\ApplicationProcessing\ExtractedApplicationData;
use App\DTOs\ApplicationProcessing\SanitizedApplicationText;
use App\Models\ApplicationDocument;
use App\Models\ApplicationExtraction;
use App\Models\LoanApplication;
use App\Services\Policy\PolicyEditionSelector;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class ApplicationPersistenceService
{
    public function __construct(
        private PolicyEditionSelector $policyEditionSelector,
    ) {}

    public function persist(
        ExtractedApplicationData $data,
        SanitizedApplicationText $sanitized,
        string $pricingSegment,
        string $sourceFile,
        string $originalName,
        string $mimeType,
        int $size
    ): LoanApplication {
        return DB::transaction(function () use (
            $data,
            $sanitized,
            $pricingSegment,
            $sourceFile,
            $originalName,
            $mimeType,
            $size
        ) {
            $applicationDate = new DateTimeImmutable(
                $data->applicationDate
            );

            $edition = $this->policyEditionSelector->select(
                $applicationDate
            );

            $application = LoanApplication::updateOrCreate(
                [
                    'reference_code' => $data->referenceCode,
                ],
                [
                    'requested_amount' => $data->requestedAmount,
                    'requested_tenor_months' => $data->requestedTenorMonths,
                    'date_of_birth' => $data->dateOfBirth,
                    'application_date' => $data->applicationDate,
                    'status' => 'submitted',
                    'policy_edition_id' => $edition->id,
                ]
            );

            ApplicationDocument::updateOrCreate(
                [
                    'loan_application_id' => $application->id,
                    'source_file' => $sourceFile,
                ],
                [
                    'type' => 'application_pack',
                    'original_name' => $originalName,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'processing_status' => 'completed',
                    'failure_reason' => null,
                ]
            );

            ApplicationExtraction::updateOrCreate(
                [
                    'loan_application_id' => $application->id,
                ],
                [
                    'net_monthly_income' => $data->netMonthlyIncome,
                    'existing_monthly_obligations' => $data->existingMonthlyObligations,
                    'employment_start_date' => $data->employmentStartDate,
                    'bureau_score' => $data->bureauScore,
                    'pricing_segment' => $pricingSegment,
                    'evidence' => [
                        'fields' => $data->evidence,
                        'removed_fields' => $sanitized->removedFields,
                        'masked_fields' => $sanitized->maskedFields,
                    ],
                    'status' => 'completed',
                    'failure_reason' => null,
                ]
            );

            return $application->fresh([
                'policyEdition',
                'documents',
                'extraction',
            ]);
        });
    }
}
