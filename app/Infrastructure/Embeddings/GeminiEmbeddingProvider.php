<?php

namespace App\Infrastructure\Embeddings;

use App\Contracts\Embeddings\EmbeddingProvider;
use App\Exceptions\Embeddings\EmbeddingGenerationException;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiEmbeddingProvider implements EmbeddingProvider
{
    private const DIMENSION = 768;

    public function embed(string $text): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-goog-api-key' => config('services.gemini.api_key'),
                    'Content-Type' => 'application/json',
                ])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/'
                    .config('services.gemini.embedding_model')
                    .':embedContent',
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => $text,
                                ],
                            ],
                        ],
                        'output_dimensionality' => self::DIMENSION,
                    ]
                );

            if ($response->failed()) {
                throw new EmbeddingGenerationException(
                    'Gemini embedding request failed: '.$response->body()
                );
            }

            $values = $response->json('embedding.values');

            if (! is_array($values)) {
                throw new EmbeddingGenerationException(
                    'Gemini returned an invalid embedding response.'
                );
            }

            if (count($values) !== self::DIMENSION) {
                throw new EmbeddingGenerationException(
                    'Expected '.self::DIMENSION
                    .' dimensions, received '
                    .count($values)
                );
            }

            return array_map(
                fn ($value) => (float) $value,
                $values
            );
        } catch (EmbeddingGenerationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new EmbeddingGenerationException(
                'Unable to generate embedding: '
                .$exception->getMessage()
            );
        }
    }

    public function dimension(): int
    {
        return self::DIMENSION;
    }
}
