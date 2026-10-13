<?php

namespace App\Infrastructure\LLM;

use App\Contracts\LLM\LanguageModelProvider;
use App\Exceptions\PolicyQA\GroundedAnswerGenerationException;
use Illuminate\Support\Facades\Http;

class GeminiLanguageModelProvider implements LanguageModelProvider
{
    public function generate(
        string $systemPrompt,
        string $userPrompt
    ): string {
        $model = config('services.gemini.chat_model');
        $apiKey = config('services.gemini.api_key');

        if (!$apiKey) {
            throw new GroundedAnswerGenerationException(
                'Gemini API key is not configured.'
            );
        }

        if (!$model) {
            throw new GroundedAnswerGenerationException(
                'Gemini chat model is not configured.'
            );
        }

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'system_instruction' => [
                        'parts' => [
                            [
                                'text' => $systemPrompt,
                            ],
                        ],
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $userPrompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                    ],
                ]
            );

        if (!$response->successful()) {
            throw new GroundedAnswerGenerationException(
                'Gemini request failed with status ' . $response->status()
            );
        }

        $text = $response->json(
            'candidates.0.content.parts.0.text'
        );

        if (!is_string($text) || trim($text) === '') {
            throw new GroundedAnswerGenerationException(
                'Gemini returned an empty response.'
            );
        }

        return trim($text);
    }
}