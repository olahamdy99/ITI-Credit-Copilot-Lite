<?php

namespace App\Console\Commands;

use App\Domain\Credit\LoanCalculator;
use App\Exceptions\ApplicationIntegrityException;
use App\Exceptions\PolicyRetrieval\InsufficientPolicyEvidenceException;
use App\Services\ApplicationProcessing\ApplicationIntegrityValidator;
use App\Services\ApplicationProcessing\ApplicationPackProcessingService;
use App\Services\Policy\PolicyEditionSelector;
use App\Services\PolicyRetrieval\PolicyRetrievalService;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Throwable;

class RunEvaluation extends Command
{
    protected $signature = 'evaluation:run';

    protected $description =
        'Run the Credit Copilot evaluation harness';

    public function handle(
        PolicyRetrievalService $retrievalService,
        PolicyEditionSelector $editionSelector,
        LoanCalculator $calculator,
        ApplicationIntegrityValidator $integrityValidator,
        ApplicationPackProcessingService $applicationProcessor
    ): int {
        $cases = require base_path('evaluation/cases.php');

        $retrievalTotal = 0;
        $retrievalPassed = 0;

        $refusalTotal = 0;
        $refusalPassed = 0;

        $calculationTotal = 0;
        $calculationPassed = 0;

        $injectionTotal = 0;
        $injectionPassed = 0;

        $this->newLine();
        $this->info('Credit Copilot Evaluation');
        $this->info('=========================');
        $this->newLine();

        foreach ($cases as $case) {
            $passed = false;
            $details = '';

            try {
                switch ($case['type']) {

                    case 'retrieval':
                        $retrievalTotal++;

                        $date = new DateTimeImmutable($case['date']);

                        $edition = $editionSelector->select($date);

                        $results = $retrievalService->search(
                            query: $case['question'],
                            applicationDate: $date,
                            policyEditionId: $edition->id,
                            limit: 5
                        );

                        foreach ($results as $result) {
                            $documentMatches =
                                $result->documentTitle ===
                                $case['expected_document'];

                            $clauseMatches =
                                ! isset($case['expected_clause']) ||
                                $result->clauseId ===
                                $case['expected_clause'];

                            $sectionMatches =
                                ! isset($case['expected_section']) ||
                                $result->sectionId ===
                                $case['expected_section'];

                            if (
                                $documentMatches &&
                                $clauseMatches &&
                                $sectionMatches
                            ) {
                                $passed = true;
                                break;
                            }
                        }

                        if ($passed) {
                            $retrievalPassed++;
                        }

                        $details = $case['question'];

                        break;

                    case 'refusal':
                        $refusalTotal++;

                        $date = new DateTimeImmutable($case['date']);

                        $edition = $editionSelector->select($date);

                        try {
                            $retrievalService->search(
                                query: $case['question'],
                                applicationDate: $date,
                                policyEditionId: $edition->id,
                                limit: 5
                            );

                            $passed = false;
                        } catch (
                            InsufficientPolicyEvidenceException
                        ) {
                            $passed = true;
                        }

                        if ($passed) {
                            $refusalPassed++;
                        }

                        $details = $case['question'];

                        break;

                    case 'calculation':
                        $calculationTotal++;

                        $installment =
                            $calculator->monthlyInstallment(
                                principal: $case['principal'],
                                annualRatePercent: $case['annual_rate'],
                                tenorMonths: $case['tenor']
                            );

                        $dbr =
                            $calculator->dbrPercentage(
                                netMonthlyIncome: $case['income'],
                                existingMonthlyObligations: $case['obligations'],
                                newMonthlyInstallment: $installment
                            );

                        $maxAmount =
                            $calculator->maxEligibleAmount(
                                netMonthlyIncome: $case['income'],
                                existingMonthlyObligations: $case['obligations'],
                                maxDbrPercentage: $case['max_dbr'],
                                annualRatePercent: $case['annual_rate'],
                                tenorMonths: $case['tenor']
                            );

                        $passed =
                            abs(
                                $installment -
                                $case['expected_installment']
                            ) < 0.01
                            &&
                            abs(
                                $dbr -
                                $case['expected_dbr']
                            ) < 0.01
                            &&
                            $maxAmount ===
                            $case['expected_max_amount'];

                        if ($passed) {
                            $calculationPassed++;
                        }

                        $details =
                            "Installment={$installment}, ".
                            "DBR={$dbr}, ".
                            "Max={$maxAmount}";

                        break;

                    case 'prompt_injection_text':
                        $injectionTotal++;

                        try {
                            $integrityValidator->validate(
                                $case['text']
                            );

                            $passed = false;
                        } catch (
                            ApplicationIntegrityException
                        ) {
                            $passed = true;
                        }

                        if ($passed) {
                            $injectionPassed++;
                        }

                        $details =
                            'Inline malicious instruction';

                        break;

                    case 'prompt_injection_file':
                        $injectionTotal++;

                        try {
                            $applicationProcessor->process(
                                $case['path']
                            );

                            $passed = false;
                        } catch (
                            ApplicationIntegrityException
                        ) {
                            $passed = true;
                        }

                        if ($passed) {
                            $injectionPassed++;
                        }

                        $details = basename($case['path']);

                        break;
                }
            } catch (Throwable $exception) {
                $passed = false;
                $details = $exception->getMessage();
            }

            $status = $passed ? 'PASS' : 'FAIL';

            $this->line(
                sprintf(
                    '[%s] %-4s %s',
                    $status,
                    $case['id'],
                    $details
                )
            );
        }

        $this->newLine();

        $retrievalRate =
            $retrievalTotal > 0
                ? round(
                    ($retrievalPassed / $retrievalTotal) * 100,
                    2
                )
                : 0;

        $refusalRate =
            $refusalTotal > 0
                ? round(
                    ($refusalPassed / $refusalTotal) * 100,
                    2
                )
                : 0;

        $calculationRate =
            $calculationTotal > 0
                ? round(
                    ($calculationPassed / $calculationTotal) * 100,
                    2
                )
                : 0;

        $injectionRate =
            $injectionTotal > 0
                ? round(
                    ($injectionPassed / $injectionTotal) * 100,
                    2
                )
                : 0;

        $this->info('Evaluation Summary');
        $this->info('==================');

        $this->table(
            ['Metric', 'Passed', 'Total', 'Score'],
            [
                [
                    'Retrieval hit-rate',
                    $retrievalPassed,
                    $retrievalTotal,
                    $retrievalRate.'%',
                ],
                [
                    'Refusal correctness',
                    $refusalPassed,
                    $refusalTotal,
                    $refusalRate.'%',
                ],
                [
                    'Calculation exactness',
                    $calculationPassed,
                    $calculationTotal,
                    $calculationRate.'%',
                ],
                [
                    'Prompt injection protection',
                    $injectionPassed,
                    $injectionTotal,
                    $injectionRate.'%',
                ],
            ]
        );

        $allPassed =
            $retrievalPassed === $retrievalTotal &&
            $refusalPassed === $refusalTotal &&
            $calculationPassed === $calculationTotal &&
            $injectionPassed === $injectionTotal;

        $this->newLine();

        if ($allPassed) {
            $this->info('Overall evaluation: PASS');

            return self::SUCCESS;
        }

        $this->error('Overall evaluation: FAIL');

        return self::FAILURE;
    }
}
