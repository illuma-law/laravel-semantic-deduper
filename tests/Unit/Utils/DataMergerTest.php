<?php

use IllumaLaw\SemanticDeduper\Utils\DataMerger;

test('it merges arrays deeply', function () {
    $canonical = [
        'name' => 'John',
        'meta' => ['age' => 30],
        'tags' => ['a', 'b']
    ];
    $duplicate = [
        'name' => 'Jon',
        'meta' => ['city' => 'NY'],
        'tags' => ['c']
    ];

    $merged = DataMerger::deepMerge($canonical, $duplicate);

    expect($merged['name'])->toBe('John'); // canonical preferred
    expect($merged['meta'])->toHaveKey('age', 30);
    expect($merged['meta'])->toHaveKey('city', 'NY');
    expect($merged['tags'])->toBe(['a', 'b']); // non-recursive overwrite by canonical
});

test('it identifies absorbable updates', function () {
    $canonical = ['name' => 'John', 'email' => '', 'phone' => null];
    $duplicate = ['name' => 'Jon', 'email' => 'john@example.com', 'phone' => '123'];

    $updates = DataMerger::identifyAbsorbableUpdates($canonical, $duplicate, ['name', 'email', 'phone']);

    expect($updates)->toHaveCount(2);
    expect($updates)->toHaveKey('email', 'john@example.com');
    expect($updates)->toHaveKey('phone', '123');
    expect($updates)->not->toHaveKey('name'); // already filled in canonical
});
