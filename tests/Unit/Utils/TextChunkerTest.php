<?php

declare(strict_types=1);

use IllumaLaw\SemanticDeduper\Utils\TextChunker;

test('it chunks text correctly', function () {
    $text = 'This is a long piece of text that we want to chunk into smaller pieces for embedding purposes.';

    $chunks = TextChunker::chunk($text, chunkSize: 20, overlap: 5);

    expect($chunks)->not->toBeEmpty();
    expect($chunks[0])->toBe('This is a long piece');

    $chunks = TextChunker::chunk('12345678901234567890', chunkSize: 10, overlap: 2);
    expect($chunks[0])->toBe('1234567890');
    expect($chunks[1])->toBe('9012345678');
});

test('it respects max chunks', function () {
    $text = str_repeat('abcde', 100);
    $chunks = TextChunker::chunk($text, chunkSize: 10, overlap: 0, maxChunks: 5);

    expect($chunks)->toHaveCount(5);
});

test('it handles empty text', function () {
    expect(TextChunker::chunk(''))->toBe([]);
});
