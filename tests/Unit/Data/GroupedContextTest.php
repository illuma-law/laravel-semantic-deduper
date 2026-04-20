<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Tests\Unit\Data;

use IllumaLaw\SemanticDeduper\Data\ContextGroup;
use IllumaLaw\SemanticDeduper\Data\ContextItem;
use IllumaLaw\SemanticDeduper\Data\GroupedContext;
use IllumaLaw\SemanticDeduper\Tests\TestCase;

uses(TestCase::class);

describe('GroupedContext', function (): void {
    describe('constructor and basic properties', function (): void {
        test('can be instantiated with empty groups', function (): void {
            $context = new GroupedContext([]);

            expect($context)->not->toBeNull();
            expect($context->groups)->toBe([]);
        });

        test('can be instantiated with groups', function (): void {
            $groups = [
                new ContextGroup('group-1', [new ContextItem(['id' => 1])]),
                new ContextGroup('group-2', [new ContextItem(['id' => 2])]),
            ];
            $context = new GroupedContext($groups);

            expect($context->groups)->toHaveCount(2);
        });
    });

    describe('isEmpty', function (): void {
        test('returns true for empty groups', function (): void {
            $context = new GroupedContext([]);

            expect($context->isEmpty())->toBeTrue();
        });

        test('returns false for non-empty groups', function (): void {
            $groups = [new ContextGroup('group-1', [new ContextItem(['id' => 1])])];
            $context = new GroupedContext($groups);

            expect($context->isEmpty())->toBeFalse();
        });

        test('returns false for group with empty items', function (): void {
            $groups = [new ContextGroup('group-1', [])];
            $context = new GroupedContext($groups);

            // The group exists even if it has no items
            expect($context->isEmpty())->toBeFalse();
        });
    });

    describe('allItems', function (): void {
        test('returns empty array for empty context', function (): void {
            $context = new GroupedContext([]);

            $result = $context->allItems();

            expect($result)->toBe([]);
        });

        test('returns all items from single group', function (): void {
            $items = [
                new ContextItem(['id' => 1]),
                new ContextItem(['id' => 2]),
            ];
            $context = new GroupedContext([new ContextGroup('group-1', $items)]);

            $result = $context->allItems();

            expect($result)->toHaveCount(2);
            expect($result[0]->get('id'))->toBe(1);
            expect($result[1]->get('id'))->toBe(2);
        });

        test('returns all items from multiple groups', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [new ContextItem(['id' => 1])]),
                new ContextGroup('group-2', [
                    new ContextItem(['id' => 2]),
                    new ContextItem(['id' => 3]),
                ]),
            ]);

            $result = $context->allItems();

            expect($result)->toHaveCount(3);
        });

        test('preserves order of groups and items', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [
                    new ContextItem(['id' => 1]),
                    new ContextItem(['id' => 2]),
                ]),
                new ContextGroup('group-2', [new ContextItem(['id' => 3])]),
            ]);

            $result = $context->allItems();

            expect($result[0]->get('id'))->toBe(1);
            expect($result[1]->get('id'))->toBe(2);
            expect($result[2]->get('id'))->toBe(3);
        });
    });

    describe('collectIdentifiers', function (): void {
        test('returns empty array for empty context', function (): void {
            $context = new GroupedContext([]);

            $result = $context->collectIdentifiers();

            expect($result)->toBe([]);
        });

        test('returns identifiers from all items', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [
                    new ContextItem(['id' => 1]),
                    new ContextItem(['id' => 2]),
                ]),
            ]);

            $result = $context->collectIdentifiers();

            expect($result)->toBe([1, 2]);
        });

        test('returns unique identifiers only', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [
                    new ContextItem(['id' => 1]),
                    new ContextItem(['id' => 1]), // Duplicate
                ]),
            ]);

            $result = $context->collectIdentifiers();

            expect($result)->toBe([1]);
        });

        test('uses custom id key', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [
                    new ContextItem(['uuid' => 'abc-123']),
                    new ContextItem(['uuid' => 'def-456']),
                ]),
            ]);

            $result = $context->collectIdentifiers('uuid');

            expect($result)->toBe(['abc-123', 'def-456']);
        });

        test('skips null identifiers', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [
                    new ContextItem(['id' => 1]),
                    new ContextItem(['name' => 'test']), // No 'id' key
                ]),
            ]);

            $result = $context->collectIdentifiers();

            expect($result)->toBe([1]);
        });

        test('collects from multiple groups', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [new ContextItem(['id' => 1])]),
                new ContextGroup('group-2', [new ContextItem(['id' => 2])]),
            ]);

            $result = $context->collectIdentifiers();

            expect($result)->toBe([1, 2]);
        });
    });

    describe('totalCount', function (): void {
        test('returns 0 for empty context', function (): void {
            $context = new GroupedContext([]);

            $result = $context->totalCount();

            expect($result)->toBe(0);
        });

        test('returns count from single group', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [
                    new ContextItem(['id' => 1]),
                    new ContextItem(['id' => 2]),
                ]),
            ]);

            $result = $context->totalCount();

            expect($result)->toBe(2);
        });

        test('returns sum from multiple groups', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [new ContextItem(['id' => 1])]),
                new ContextGroup('group-2', [
                    new ContextItem(['id' => 2]),
                    new ContextItem(['id' => 3]),
                ]),
            ]);

            $result = $context->totalCount();

            expect($result)->toBe(3);
        });

        test('handles empty groups in count', function (): void {
            $context = new GroupedContext([
                new ContextGroup('group-1', [new ContextItem(['id' => 1])]),
                new ContextGroup('group-2', []),
            ]);

            $result = $context->totalCount();

            expect($result)->toBe(1);
        });
    });

    describe('groups property', function (): void {
        test('groups is readonly', function (): void {
            $context = new GroupedContext([]);

            try {
                $ref = new \ReflectionProperty($context, 'groups');
                $ref->setValue($context, []);
            } catch (\Error $e) {
                expect($e->getMessage())->toContain('readonly');
            }
        });
    });
});
