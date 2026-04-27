<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Utils;

final class StringSimilarity
{
    /**
     * @return float Similarity percentage (0–100).
     */
    public static function score(string $a, string $b): float
    {
        if ($a === $b) {
            return 100.0;
        }

        if ($a === '' || $b === '') {
            return 0.0;
        }

        similar_text($a, $b, $percent);

        return (float) $percent;
    }

    /**
     * @return float Similarity percentage (0–100).
     */
    public static function levenshteinScore(string $a, string $b): float
    {
        if ($a === $b) {
            return 100.0;
        }

        $maxLen = max(mb_strlen($a), mb_strlen($b));

        if ($maxLen === 0) {
            return 100.0;
        }

        $distance = levenshtein($a, $b);

        return (1 - $distance / $maxLen) * 100;
    }
}
