<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PolicyIngestion\Extractors\CsvExtractor;
use App\Services\PolicyIngestion\Extractors\MarkdownExtractor;
use App\Services\PolicyIngestion\PolicyTextExtractor;
use App\Services\PolicyIngestion\Extractors\PdfExtractor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
