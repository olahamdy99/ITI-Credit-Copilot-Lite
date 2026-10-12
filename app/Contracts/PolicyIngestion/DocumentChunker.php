<?php

namespace App\Contracts\PolicyIngestion;

interface DocumentChunker
{
    public function chunk(string $text): array;
}