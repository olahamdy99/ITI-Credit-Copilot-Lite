<?php

namespace App\Console\Commands;

use App\Exceptions\PolicyRetrieval\InsufficientPolicyEvidenceException;
use Illuminate\Console\Command;

class SearchPolicy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy:search
    {query : Search query}
    {date : Application date in Y-m-d format}
    {--limit=5 : Maximum number of results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(
        \App\Services\PolicyRetrieval\PolicyRetrievalService $service
    ): int {
        $query = $this->argument('query');
        $date = new \DateTimeImmutable(
            $this->argument('date')
        );

        $limit = (int) $this->option('limit');

        try {
            $results = $service->search(
                query: $query,
                applicationDate: $date,
                policyEditionId: null,
                limit: $limit,
            );
        } catch (InsufficientPolicyEvidenceException $exception) {
            $this->warn(
                'Insufficient policy evidence to answer this question.'
            );

            return self::SUCCESS;
        }

        if ($results === []) {
            $this->warn('No policy results found.');

            return self::SUCCESS;
        }

        foreach ($results as $index => $result) {
            $this->line('----------------------------------------');
            $this->line('Rank: '.($index + 1));
            $this->line(
                'Document: '.$result->documentTitle
            );
            $this->line(
                'Section: '.($result->sectionId ?? 'N/A')
            );
            $this->line(
                'Clause: '.($result->clauseId ?? 'N/A')
            );
            $this->line(
                'Similarity: '.
                round((float) $result->similarity, 4)
            );
            $this->line('Content:');
            $this->line($result->content);
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
