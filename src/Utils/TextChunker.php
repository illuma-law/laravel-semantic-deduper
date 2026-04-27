<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Utils;

use Illuminate\Support\Str;

final class TextChunker
{
    /**
     * @param  string  $text  The text to chunk.
     * @param  int  $chunkSize  Maximum character length of each chunk.
     * @param  int  $overlap  Number of overlapping characters between chunks.
     * @param  int  $maxChunks  Maximum number of chunks to return.
     * @return list<string>
     */
    public static function chunk(string $text, int $chunkSize = 2000, int $overlap = 200, int $maxChunks = 16): array
    {
        $normalized = Str::squish($text);

        if ($normalized === '') {
            return [];
        }

        $chunkSize = max($chunkSize, 1);
        $overlap = max(min($overlap, $chunkSize - 1), 0);
        $maxChunks = max($maxChunks, 1);
        $step = max($chunkSize - $overlap, 1);

        $length = Str::length($normalized);
        $chunks = [];

        for ($offset = 0; $offset < $length && count($chunks) < $maxChunks; $offset += $step) {
            $chunk = trim(Str::substr($normalized, $offset, $chunkSize));

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }
}
