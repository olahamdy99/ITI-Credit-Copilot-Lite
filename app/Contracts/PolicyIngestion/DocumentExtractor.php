<?php

namespace App\Contracts\PolicyIngestion;

interface DocumentExtractor
{
    public function supports(string $format): bool;

    public function extract(string $path): string;
}