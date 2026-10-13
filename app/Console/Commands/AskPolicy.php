<?php

namespace App\Console\Commands;

use App\Exceptions\PolicyQA\GroundedAnswerGenerationException;
use App\Exceptions\PolicyRetrieval\InsufficientPolicyEvidenceException;
use App\Services\PolicyQA\PolicyQuestionAnsweringService;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Throwable;

class AskPolicy extends Command
{
    protected $signature = 'policy:ask
        {question : Policy question}
        {date : Application date in Y-m-d format}';

    protected $description =
        'Ask a grounded question against policy documents';

    public function handle(
        PolicyQuestionAnsweringService $service
    ): int {
        try {
            $date = new DateTimeImmutable(
                $this->argument('date')
            );

            $answer = $service->answer(
                question: $this->argument('question'),
                applicationDate: $date,
            );

            $this->newLine();
            $this->info('Answer:');
            $this->line($answer->answer);

            $this->newLine();
            $this->info('Citations:');

            foreach ($answer->citations as $citation) {
                $this->line(
                    sprintf(
                        '[%s] %s | Section: %s | Clause: %s | Page: %s',
                        $citation['id'],
                        $citation['document'],
                        $citation['section_id'] ?? 'N/A',
                        $citation['clause_id'] ?? 'N/A',
                        $citation['page_number'] ?? 'N/A',
                    )
                );
            }

            return self::SUCCESS;
        } catch (InsufficientPolicyEvidenceException $exception) {
            $this->warn(
                'Insufficient policy evidence to answer this question.'
            );

            return self::SUCCESS;
        } catch (GroundedAnswerGenerationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error(
                'Unexpected error: ' . $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}