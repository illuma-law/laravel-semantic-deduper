<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Utils;

final class DataMerger
{
    /**
     * Recursively merge two arrays, preferring values from the canonical array.
     *
     * @param  array<string, mixed>  $canonical
     * @param  array<string, mixed>  $duplicate
     * @return array<string, mixed>
     */
    public static function deepMerge(array $canonical, array $duplicate): array
    {
        $result = $duplicate;

        foreach ($canonical as $key => $value) {
            $duplicateValue = $result[$key] ?? null;

            if (is_array($duplicateValue) && is_array($value)) {
                /** @var array<string, mixed> $value */
                /** @var array<string, mixed> $duplicateValue */
                $result[$key] = self::deepMerge($value, $duplicateValue);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Identify which fields from a duplicate can be absorbed into a canonical record.
     * Only absorbs if the canonical field is "blank" and the duplicate field is "filled".
     *
     * @param  array<string, mixed>  $canonical
     * @param  array<string, mixed>  $duplicate
     * @param  array<int, string>  $fields
     * @return array<string, mixed> The updates to apply to the canonical record.
     */
    public static function identifyAbsorbableUpdates(array $canonical, array $duplicate, array $fields): array
    {
        $updates = [];

        foreach ($fields as $field) {
            $canonicalValue = $canonical[$field] ?? null;
            $duplicateValue = $duplicate[$field] ?? null;

            if (self::isBlank($canonicalValue) && ! self::isBlank($duplicateValue)) {
                $updates[$field] = $duplicateValue;
            }
        }

        return $updates;
    }

    /**
     * Internal helper to check if a value is blank (matches Laravel's blank()).
     */
    private static function isBlank(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof \Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}
