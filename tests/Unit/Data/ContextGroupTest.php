<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Tests\Unit\Data;

use IllumaLaw\SemanticDeduper\Data\ContextGroup;
use IllumaLaw\SemanticDeduper\Data\ContextItem;
use IllumaLaw\SemanticDeduper\Tests\TestCase;

uses(TestCase::class);

describe('ContextGroup', function (): void {
    test('can be instantiated with label and items', function (): void {
        $items = [
            new ContextItem(['id' => 1]),
            new ContextItem(['id' => 2]),
        ];
        $group = new ContextGroup('test-label', $items);

        expect($group)->toBeInstanceOf(ContextGroup::class);
        expect($group->label)->toBe('test-label');
        expect($group->items)->toHaveCount(2);
    });

    test('can be instantiated with metadata', function (): void {
        $items = [new ContextItem(['id' => 1])];
        $metadata = ['source' => 'web', 'score' => 0.95];
        $group = new ContextGroup('test-label', $items, $metadata);

        expect($group->metadata)->toBe($metadata);
    });

    test('metadata defaults to empty array', function (): void {
        $items = [new ContextItem(['id' => 1])];
        $group = new ContextGroup('test-label', $items);

        expect($group->metadata)->toBe([]);
    });

    test('can have empty items array', function (): void {
        $group = new ContextGroup('empty-group', []);

        expect($group->items)->toBe([]);
    });

    test('items are readonly', function (): void {
        $items = [new ContextItem(['id' => 1])];
        $group = new ContextGroup('test-label', $items);

        try {
            // @phpstan-ignore-next-line
            $group->items = [];
        } catch (\Error $e) {
            expect($e->getMessage())->toContain('readonly');
        }
    });

    test('label is readonly', function (): void {
        $group = new ContextGroup('test-label', []);

        try {
            // @phpstan-ignore-next-line
            $group->label = 'new-label';
        } catch (\Error $e) {
            expect($e->getMessage())->toContain('readonly');
        }
    });
});
