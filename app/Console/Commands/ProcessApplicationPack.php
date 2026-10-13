<?php

namespace App\Console\Commands;

use App\Contracts\ApplicationProcessing\ApplicationDataExtractor;
use App\Services\ApplicationProcessing\ApplicationPackProcessingService;
use App\Services\ApplicationProcessing\ApplicationPersistenceService;
use App\Services\ApplicationProcessing\PricingSegmentResolver;
use Illuminate\Console\Command;
use Throwable;

class ProcessApplicationPack extends Command
{
    protected $signature = 'application:process
        {path : Path to the application pack PDF}';

    protected $description =
        'Extract, sanitize, structure, verify, and persist an application pack';

    public function handle(
        ApplicationPackProcessingService $processor,
        ApplicationDataExtractor $extractor,
        ApplicationPersistenceService $persistence,
        PricingSegmentResolver $pricingSegmentResolver
    ): int {
        $path = $this->argument('path');

        try {
            $sanitized = $processor->process($path);
            $pricingSegment = $pricingSegmentResolver->resolve(
                $sanitized->text
            );

            $data = $extractor->extract(
                $sanitized->text
            );

            $application = $persistence->persist(
                data: $data,
                sanitized: $sanitized,
                pricingSegment: $pricingSegment,
                sourceFile: $path,
                originalName: basename($path),
                mimeType: 'application/pdf',
                size: filesize($path),
            );

            $this->info('Application processed successfully.');

            $this->table(
                ['Field', 'Value'],
                [
                    ['Reference', $application->reference_code],
                    ['Policy edition', $application->policyEdition?->code],
                    ['Requested amount', $application->requested_amount],
                    ['Tenor', $application->requested_tenor_months],
                    ['Net income', $application->extraction?->net_monthly_income],
                    ['Obligations', $application->extraction?->existing_monthly_obligations],
                    ['Bureau score', $application->extraction?->bureau_score],
                    ['Pricing segment', $application->extraction?->pricing_segment],
                    ['Status', $application->extraction?->status],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                'Application processing failed: ' .
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}