<?php

namespace App\Contracts\LLM;

interface LanguageModelProvider
{
    public function generate(string $systemPrompt, string $userPrompt): string;
}
