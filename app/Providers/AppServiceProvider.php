<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PolicyIngestion\Extractors\CsvExtractor;
use App\Services\PolicyIngestion\Extractors\MarkdownExtractor;
use App\Services\PolicyIngestion\PolicyTextExtractor;
use App\Services\PolicyIngestion\Extractors\PdfExtractor;
use App\Contracts\PolicyIngestion\DocumentChunker;
use App\Services\PolicyIngestion\Chunkers\SectionChunker;
use App\Services\PolicyIngestion\Chunkers\CsvRowChunker;
use App\Services\PolicyIngestion\PolicyChunker;
use App\Contracts\Embeddings\EmbeddingProvider;
use App\Infrastructure\Embeddings\GeminiEmbeddingProvider;
use App\Contracts\PolicyRetrieval\PolicySearchRepository;
use App\Infrastructure\PolicyRetrieval\PgVectorPolicySearchRepository;
use App\Contracts\LLM\LanguageModelProvider;
use App\Infrastructure\LLM\GeminiLanguageModelProvider;
use App\Contracts\ApplicationProcessing\ApplicationPackExtractor;
use App\Infrastructure\ApplicationProcessing\PdfApplicationPackExtractor;
use App\Contracts\ApplicationProcessing\ApplicationDataExtractor;
use App\Services\ApplicationProcessing\LlmApplicationDataExtractor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Policy document extractors
        |--------------------------------------------------------------------------
        */

        $this->app->tag(
            [
                MarkdownExtractor::class,
                CsvExtractor::class,
                PdfExtractor::class,
            ],
            'policy.extractors'
        );

        $this->app->bind(
            PolicyTextExtractor::class,
            function ($app) {
                return new PolicyTextExtractor(
                    $app->tagged('policy.extractors')
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Policy document chunkers
        |--------------------------------------------------------------------------
        */

        $this->app->tag(
            [
                SectionChunker::class,
                CsvRowChunker::class,
            ],
            'policy.chunkers'
        );

        $this->app->bind(
            PolicyChunker::class,
            function ($app) {
                return new PolicyChunker(
                    $app->tagged('policy.chunkers')
                );
            }
        );

        $this->app->bind(
            EmbeddingProvider::class,
            GeminiEmbeddingProvider::class
        );

        $this->app->bind(
            PolicySearchRepository::class,
            PgVectorPolicySearchRepository::class
        );

        $this->app->bind(
            LanguageModelProvider::class,
            GeminiLanguageModelProvider::class
        );

        $this->app->bind(
            ApplicationPackExtractor::class,
            PdfApplicationPackExtractor::class
        );
        
        $this->app->bind(
            ApplicationDataExtractor::class,
            LlmApplicationDataExtractor::class
        );
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
