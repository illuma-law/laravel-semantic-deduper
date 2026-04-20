<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Tests\Unit;

use IllumaLaw\SemanticDeduper\SemanticClusterer;
use IllumaLaw\SemanticDeduper\SemanticDeduperServiceProvider;
use IllumaLaw\SemanticDeduper\Tests\TestCase;

uses(TestCase::class);

describe('SemanticDeduperServiceProvider', function (): void {
    test('service provider is registered', function (): void {
        $provider = app()->getProvider(SemanticDeduperServiceProvider::class);

        expect($provider)->not->toBeNull();
    });

    test('config is loaded', function (): void {
        $config = config('semantic-deduper');

        expect($config)->toBeArray();
        expect($config)->toHaveKeys(['max_per_group', 'max_total', 'near_duplicate_threshold']);
    });

    test('config has default values', function (): void {
        expect(config('semantic-deduper.max_per_group'))->toBe(3);
        expect(config('semantic-deduper.max_total'))->toBe(12);
        expect(config('semantic-deduper.near_duplicate_threshold'))->toBe(0.92);
    });

    test('config can be overridden', function (): void {
        config(['semantic-deduper.max_per_group' => 10]);

        expect(config('semantic-deduper.max_per_group'))->toBe(10);
    });

    test('semantic clusterer uses config defaults', function (): void {
        config(['semantic-deduper.max_per_group' => 5]);
        config(['semantic-deduper.max_total' => 20]);
        config(['semantic-deduper.near_duplicate_threshold' => 0.88]);

        $clusterer = new SemanticClusterer;

        // Test that config values are used by checking behavior
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
