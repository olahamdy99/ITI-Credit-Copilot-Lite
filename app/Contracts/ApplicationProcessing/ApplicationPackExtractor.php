<?php

namespace App\Contracts\ApplicationProcessing;

interface ApplicationPackExtractor
{
    public function extract(string $path): string;
}
