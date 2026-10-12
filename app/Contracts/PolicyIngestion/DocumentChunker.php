<?php

namespace App\Contracts\PolicyIngestion;

interface DocumentChunker
{
    public function supports(string $format): bool;

    public function chunk(string $text): array;
}