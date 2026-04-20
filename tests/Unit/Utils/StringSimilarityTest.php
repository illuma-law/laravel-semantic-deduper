<?php

use IllumaLaw\SemanticDeduper\Utils\StringSimilarity;

test('it calculates similarity score correctly', function () {
    expect(StringSimilarity::score('Laravel', 'Laravel'))->toBe(100.0);
    expect(StringSimilarity::score('Laravel', 'Laraval'))->toBeGreaterThan(80.0);
    expect(StringSimilarity::score('Laravel', 'Django'))->toBeLessThan(30.0);
    expect(StringSimilarity::score('', ''))->toBe(100.0);
    expect(StringSimilarity::score('A', ''))->toBe(0.0);
});

test('it calculates levenshtein score correctly', function () {
    expect(StringSimilarity::levenshteinScore('Laravel', 'Laravel'))->toBe(100.0);
    expect(StringSimilarity::levenshteinScore('Laravel', 'Laraval'))->toBeGreaterThan(80.0);
    expect(StringSimilarity::levenshteinScore('Laravel', 'Django'))->toBeLessThan(20.0);
    expect(StringSimilarity::levenshteinScore('', ''))->toBe(100.0);
});
