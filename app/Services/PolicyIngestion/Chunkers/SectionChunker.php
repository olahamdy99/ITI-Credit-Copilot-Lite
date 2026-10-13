<?php

namespace App\Services\PolicyIngestion\Chunkers;

use App\Contracts\PolicyIngestion\DocumentChunker;

class SectionChunker implements DocumentChunker
{
    public function chunk(string $text): array
    {
        $lines = preg_split('/\R/', $text);

        $chunks = [];
        $currentChunk = null;
        $chunkIndex = 0;

        $currentSectionId = null;
        $currentSectionTitle = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $heading = $this->parseHeading($line);

            if ($heading !== null) {
                if (
                    $currentChunk !== null &&
                    trim($currentChunk['content']) !== ''
                ) {
                    $chunks[] = $currentChunk;
                }

                if ($heading['clause_id'] === null) {
                    $currentSectionId = $heading['section_id'];
                    $currentSectionTitle = $heading['title'];

                    $currentChunk = [
                        'chunk_index' => $chunkIndex++,
                        'section_id' => $currentSectionId,
                        'section_title' => $currentSectionTitle,
                        'clause_id' => null,
                        'content' => '',
                    ];
                } else {
                    $currentChunk = [
                        'chunk_index' => $chunkIndex++,
                        'section_id' => $currentSectionId
                            ?? $heading['section_id'],
                        'section_title' => $currentSectionTitle,
                        'clause_id' => $heading['clause_id'],
                        'content' => $heading['title'],
                    ];
                }

                continue;
            }

            if ($currentChunk === null) {
                continue;
            }

            $currentChunk['content'] .=
                ($currentChunk['content'] === '' ? '' : PHP_EOL)
                .$line;
        }

        if (
            $currentChunk !== null &&
            trim($currentChunk['content']) !== ''
        ) {
            $chunks[] = $currentChunk;
        }

        return array_values(
            array_map(
                function (array $chunk, int $index) {
                    $chunk['chunk_index'] = $index;

                    return $chunk;
                },
                $chunks,
                array_keys($chunks)
            )
        );
    }

    private function parseHeading(string $line): ?array
    {
        $cleanLine = preg_replace(
            '/^#{1,6}\s*/',
            '',
            $line
        );

        if (! preg_match(
            '/^(?<id>[A-Z]{1,4}-\d+(?:\.\d+)?)(?:\.?\s+(?<title>.+))?$/',
            $cleanLine,
            $matches
        )) {
            return null;
        }

        $id = $matches['id'];

        $title = isset($matches['title'])
            ? trim($matches['title'])
            : null;

        $isClause = str_contains($id, '.');

        return [
            'section_id' => $isClause
                ? substr($id, 0, strrpos($id, '.'))
                : $id,

            'clause_id' => $isClause ? $id : null,

            'title' => $title,
        ];
    }

    public function supports(string $format): bool
    {
        return in_array(
            strtolower($format),
            ['markdown', 'md', 'pdf'],
            true
        );
    }
}
