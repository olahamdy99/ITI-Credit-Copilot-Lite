<?php

namespace App\Contracts\ApplicationProcessing;

use App\DTOs\ApplicationProcessing\ExtractedApplicationData;

interface ApplicationDataExtractor
{
    public function extract(string $sanitizedText): ExtractedApplicationData;
}
