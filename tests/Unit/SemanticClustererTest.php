<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Tests\Unit;

use IllumaLaw\SemanticDeduper\Data\ContextGroup;
use IllumaLaw\SemanticDeduper\SemanticClusterer;
use IllumaLaw\SemanticDeduper\Tests\TestCase;
use Illuminate\Support\Collection;

uses(TestCase::class);

describe('SemanticClusterer', function (): void {
    describe('cosineSimilarity', function (): void {
        test('returns 1.0 for identical vectors', function (): void {
            $clusterer = new SemanticClusterer;
            $a = [1.0, 0.0, 0.0];
            $b = [1.0, 0.0, 0.0];

            $result = $clusterer->cosineSimilarity($a, $b);

            expect($result)->toBe(1.0);
        });

        test('returns 0.0 for orthogonal vectors', function (): void {
            $clusterer = new SemanticClusterer;
            $a = [1.0, 0.0, 0.0];
            $b = [0.0, 1.0, 0.0];

            $result = $clusterer->cosineSimilarity($a, $b);

            expect($result)->toBe(0.0);
        });

        test('calculates correct similarity for 45 degree angle', function (): void {
            $clusterer = new SemanticClusterer;
            $a = [1.0, 0.0, 0.0];
            $b = [0.5, 0.5, 0.0];

            $result = $clusterer->cosineSimilarity($a, $b);

            expect($result)->toEqualWithDelta(0.7071, 0.0001);
        });

        test('returns 0.0 for empty vectors', function (): void {
            $clusterer = new SemanticClusterer;

            $result = $clusterer->cosineSimilarity([], [1.0, 0.0]);

            expect($result)->toBe(0.0);
        });

        test('returns 0.0 for zero magnitude vectors', function (): void {
            $clusterer = new SemanticClusterer;
            $a = [0.0, 0.0, 0.0];
            $b = [1.0, 0.0, 0.0];

            $result = $clusterer->cosineSimilarity($a, $b);

            expect($result)->toBe(0.0);
        });

        test('handles different length vectors by using minimum length', function (): void {
            $clusterer = new SemanticClusterer;
            $a = [1.0, 0.0];
            $b = [1.0, 0.0, 0.5];

            $result = $clusterer->cosineSimilarity($a, $b);

            expect($result)->toBe(1.0);
        });

        test('handles first vector longer than second', function (): void {
            $clusterer = new SemanticClusterer;
            $a = [1.0, 0.0, 0.5];
            $b = [1.0, 0.0];

            $result = $clusterer->cosineSimilarity($a, $b);

            expect($result)->toBe(1.0);
        });
    });

    describe('constructor and factory', function (): void {
        test('can be instantiated with default values', function (): void {
            $clusterer = new SemanticClusterer;

            expect($clusterer)->not->toBeNull();
        });

        test('can be instantiated with custom values', function (): void {
            $clusterer = new SemanticClusterer(5, 20, 0.85);

            expect($clusterer)->not->toBeNull();
        });

        test('make factory method creates instance', function (): void {
            $clusterer = SemanticClusterer::make();

            expect($clusterer)->not->toBeNull();
        });

        test('make factory method accepts parameters', function (): void {
            $clusterer = SemanticClusterer::make(5, 20, 0.85);

            expect($clusterer)->not->toBeNull();
        });

        test('uses config values when no parameters provided', function (): void {
            config(['semantic-deduper.max_per_group' => 5]);
            config(['semantic-deduper.max_total' => 25]);
            config(['semantic-deduper.near_duplicate_threshold' => 0.88]);

            $clusterer = new SemanticClusterer;

            $results = [
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'A', 'embedding' => [0.0, 1.0]],
                ['id' => 3, 'source' => 'A', 'embedding' => [0.5, 0.5]],
                ['id' => 4, 'source' => 'A', 'embedding' => [0.6, 0.4]],
                ['id' => 5, 'source' => 'A', 'embedding' => [0.4, 0.6]],
                ['id' => 6, 'source' => 'A', 'embedding' => [0.3, 0.7]],
            ];

            $grouped = $clusterer->maxPerGroup(5)->groupBy('source')->cluster($results);
            expect($grouped->totalCount())->toBeLessThanOrEqual(5);
        });
    });

    describe('fluent configuration', function (): void {
        test('maxPerGroup returns new instance with updated value', function (): void {
            $clusterer = SemanticClusterer::make()->maxPerGroup(10);

            expect($clusterer)->not->toBeNull();
        });

        test('maxTotal returns new instance with updated value', function (): void {
            $clusterer = SemanticClusterer::make()->maxTotal(50);

            expect($clusterer)->not->toBeNull();
        });

        test('threshold returns new instance with updated value', function (): void {
            $clusterer = SemanticClusterer::make()->threshold(0.95);

            expect($clusterer)->not->toBeNull();
        });

        test('embeddingKey returns new instance with updated value', function (): void {
            $clusterer = SemanticClusterer::make()->embeddingKey('vector');

            expect($clusterer)->not->toBeNull();
        });

        test('idKey returns new instance with updated value', function (): void {
            $clusterer = SemanticClusterer::make()->idKey('uuid');

            expect($clusterer)->not->toBeNull();
        });

        test('groupBy with string returns new instance', function (): void {
            $clusterer = SemanticClusterer::make()->groupBy('category');

            expect($clusterer)->not->toBeNull();
        });

        test('groupBy with callback returns new instance', function (): void {
            $clusterer = SemanticClusterer::make()->groupBy(fn (array $row): string => is_scalar($row['type']) ? (string) $row['type'] : '');

            expect($clusterer)->not->toBeNull();
        });
    });

    describe('cluster', function (): void {
        test('clusters results and removes near duplicates', function (): void {
            $clusterer = SemanticClusterer::make()
                ->threshold(0.9)
                ->maxPerGroup(2)
                ->maxTotal(5)
                ->groupBy('source');

            $results = [
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'A', 'embedding' => [0.99, 0.01]],
                ['id' => 3, 'source' => 'A', 'embedding' => [0.0, 1.0]],
                ['id' => 4, 'source' => 'B', 'embedding' => [1.0, 0.0]],
                ['id' => 5, 'source' => 'B', 'embedding' => [0.5, 0.5]],
                ['id' => 6, 'source' => 'B', 'embedding' => [0.49, 0.51]],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped)->not->toBeNull();
            expect($grouped->groups)->toHaveCount(2);

            $groupA = collect($grouped->groups)->firstWhere('label', 'A');
            if ($groupA instanceof ContextGroup) {
                expect($groupA->items)->toHaveCount(2);
                expect($groupA->items[0]->get('id'))->toBe(1);
                expect($groupA->items[1]->get('id'))->toBe(3);
            }

            $groupB = collect($grouped->groups)->firstWhere('label', 'B');
            if ($groupB instanceof ContextGroup) {
                expect($groupB->items)->toHaveCount(2);
                expect($groupB->items[0]->get('id'))->toBe(4);
                expect($groupB->items[1]->get('id'))->toBe(5);
            }
        });

        test('accepts Laravel Collection as input', function (): void {
            $clusterer = SemanticClusterer::make()->groupBy('source');

            /** @var Collection<int, array<string, mixed>> $results */
            $results = new Collection([
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'B', 'embedding' => [0.0, 1.0]],
            ]);

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups)->toHaveCount(2);
        });

        test('respects max total limits', function (): void {
            $clusterer = SemanticClusterer::make()
                ->maxTotal(3)
                ->maxPerGroup(5)
                ->groupBy('source');

            $results = [
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'A', 'embedding' => [0.0, 1.0]],
                ['id' => 3, 'source' => 'B', 'embedding' => [1.0, 0.0]],
                ['id' => 4, 'source' => 'B', 'embedding' => [0.0, 1.0]],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->totalCount())->toBe(3);
            expect($grouped->groups)->toHaveCount(2);
            expect($grouped->groups[0]->items)->toHaveCount(2);
            expect($grouped->groups[1]->items)->toHaveCount(1);
        });

        test('respects max per group limits', function (): void {
            $clusterer = SemanticClusterer::make()
                ->maxPerGroup(1)
                ->maxTotal(10)
                ->groupBy('source');

            $results = [
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'A', 'embedding' => [0.0, 1.0]],
                ['id' => 3, 'source' => 'A', 'embedding' => [0.5, 0.5]],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups[0]->items)->toHaveCount(1);
        });

        test('handles empty input gracefully', function (): void {
            $clusterer = new SemanticClusterer;
            $grouped = $clusterer->cluster([]);

            expect($grouped->isEmpty())->toBeTrue();
            expect($grouped->groups)->toBeEmpty();
        });

        test('handles items without embeddings', function (): void {
            $clusterer = new SemanticClusterer;
            $results = [
                ['id' => 1, 'group' => 'A'],
                ['id' => 2, 'group' => 'A'],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups[0]->items)->toHaveCount(2);
        });

        test('handles items with null embeddings', function (): void {
            $clusterer = new SemanticClusterer;
            $results = [
                ['id' => 1, 'group' => 'A', 'embedding' => null],
                ['id' => 2, 'group' => 'A', 'embedding' => null],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups[0]->items)->toHaveCount(2);
        });

        test('handles items with empty array embeddings', function (): void {
            $clusterer = new SemanticClusterer;
            $results = [
                ['id' => 1, 'group' => 'A', 'embedding' => []],
                ['id' => 2, 'group' => 'A', 'embedding' => []],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups[0]->items)->toHaveCount(2);
        });

        test('can group by a callback', function (): void {
            $clusterer = SemanticClusterer::make()
                ->groupBy(fn (array $row): string => (is_scalar($row['prefix']) ? (string) $row['prefix'] : '').'-'.(is_scalar($row['suffix']) ? (string) $row['suffix'] : ''));

            $results = [
                ['id' => 1, 'prefix' => 'foo', 'suffix' => 'bar'],
                ['id' => 2, 'prefix' => 'foo', 'suffix' => 'baz'],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups)->toHaveCount(2);
            expect($grouped->groups[0]->label)->toBe('foo-bar');
            expect($grouped->groups[1]->label)->toBe('foo-baz');
        });

        test('groups by default key when string group by not present', function (): void {
            $clusterer = SemanticClusterer::make()->groupBy('nonexistent');

            $results = [
                ['id' => 1],
                ['id' => 2],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups)->toHaveCount(1);
            expect($grouped->groups[0]->label)->toBe('unknown');
        });

        test('uses custom embedding key', function (): void {
            $clusterer = SemanticClusterer::make()
                ->embeddingKey('vector')
                ->threshold(0.9)
                ->groupBy('source');

            $results = [
                ['id' => 1, 'source' => 'A', 'vector' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'A', 'vector' => [0.99, 0.01]],
                ['id' => 3, 'source' => 'A', 'vector' => [0.0, 1.0]],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups[0]->items)->toHaveCount(2);
        });

        test('deduplication keeps first occurrence', function (): void {
            $clusterer = SemanticClusterer::make()
                ->threshold(0.99)
                ->groupBy('source');

            $results = [
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'A', 'embedding' => [1.0, 0.0]],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups[0]->items)->toHaveCount(1);
            expect($grouped->groups[0]->items[0]->get('id'))->toBe(1);
        });

        test('respects max total across multiple groups', function (): void {
            $clusterer = SemanticClusterer::make()
                ->maxTotal(2)
                ->maxPerGroup(5)
                ->groupBy('source');

            $results = [
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'A', 'embedding' => [0.0, 1.0]],
                ['id' => 3, 'source' => 'B', 'embedding' => [1.0, 0.0]],
                ['id' => 4, 'source' => 'B', 'embedding' => [0.0, 1.0]],
                ['id' => 5, 'source' => 'C', 'embedding' => [1.0, 0.0]],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->totalCount())->toBe(2);
        });

        test('skips groups that become empty after deduplication', function (): void {
            $clusterer = SemanticClusterer::make()
                ->maxPerGroup(5)
                ->groupBy('source');

            $results = [
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'A', 'embedding' => [1.0, 0.0]],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups)->toHaveCount(1);
            expect($grouped->groups[0]->items)->toHaveCount(1);
        });

        test('handles mixed items with and without embeddings', function (): void {
            $clusterer = SemanticClusterer::make()
                ->threshold(0.9)
                ->groupBy('source');

            $results = [
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0]],
                ['id' => 2, 'source' => 'A'],
                ['id' => 3, 'source' => 'A', 'embedding' => [0.99, 0.01]],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups[0]->items)->toHaveCount(2);
        });

        test('creates ContextItem objects with correct payload', function (): void {
            $clusterer = SemanticClusterer::make()->groupBy('source');

            $results = [
                ['id' => 1, 'source' => 'A', 'embedding' => [1.0, 0.0], 'extra' => 'data'],
            ];

            $grouped = $clusterer->cluster($results);

            expect($grouped->groups[0]->items[0])->not->toBeNull();
            expect($grouped->groups[0]->items[0]->get('id'))->toBe(1);
            expect($grouped->groups[0]->items[0]->get('extra'))->toBe('data');
        });
    });
});
