<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Tests\Unit\Data;

use IllumaLaw\SemanticDeduper\Data\ContextItem;
use IllumaLaw\SemanticDeduper\Tests\TestCase;

uses(TestCase::class);

describe('ContextItem', function (): void {
    test('can be instantiated with payload', function (): void {
        $item = new ContextItem(['id' => 1, 'name' => 'test']);

        expect($item)->toBeInstanceOf(ContextItem::class);
        expect($item->payload)->toBe(['id' => 1, 'name' => 'test']);
    });

    test('get method returns value for existing key', function (): void {
        $item = new ContextItem(['id' => 1, 'name' => 'test']);

        $result = $item->get('id');

        expect($result)->toBe(1);
    });

    test('get method returns null for non-existing key', function (): void {
        $item = new ContextItem(['id' => 1]);

        $result = $item->get('nonexistent');

        expect($result)->toBeNull();
    });

    test('get method returns default for non-existing key', function (): void {
        $item = new ContextItem(['id' => 1]);

        $result = $item->get('nonexistent', 'default_value');

        expect($result)->toBe('default_value');
    });

    test('get method returns null when default is null', function (): void {
        $item = new ContextItem(['id' => 1]);

        $result = $item->get('nonexistent', null);

        expect($result)->toBeNull();
    });

    test('magic __get returns value for existing key', function (): void {
        $item = new ContextItem(['id' => 1, 'name' => 'test']);

        $result = $item->id;

        expect($result)->toBe(1);
    });

    test('magic __get returns null for non-existing key', function (): void {
        $item = new ContextItem(['id' => 1]);

        $result = $item->nonexistent;

        expect($result)->toBeNull();
    });

    test('handles nested array payload', function (): void {
        $item = new ContextItem([
            'id' => 1,
            'metadata' => ['key' => 'value'],
        ]);

        expect($item->get('metadata'))->toBe(['key' => 'value']);
        expect($item->metadata)->toBe(['key' => 'value']);
    });

    test('handles empty payload', function (): void {
        $item = new ContextItem([]);

        expect($item->get('anything'))->toBeNull();
        expect($item->anything)->toBeNull();
    });

    test('payload is readonly', function (): void {
        $item = new ContextItem(['id' => 1]);

        // Attempting to modify should not work
        try {
            // @phpstan-ignore-next-line
            $item->payload = ['id' => 2];
        } catch (\Error $e) {
            expect($e->getMessage())->toContain('readonly');
        }
    });
});
