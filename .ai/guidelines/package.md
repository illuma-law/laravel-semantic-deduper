---
description: Cosine-similarity semantic deduplication for Laravel collections — RAG pipelines, context window optimization
---

# laravel-semantic-deduper

Fast semantic deduplication for Laravel collections and arrays using cosine similarity on vector embeddings. Removes near-duplicate content from RAG context windows.

## Namespace

`IllumaLaw\SemanticDeduper`

## Key Classes & DTOs

- `SemanticClusterer` — main service; groups and deduplicates in one pass
- `GroupedContext` DTO — deduplicated output
- `ContextGroup` DTO — one group of diverse items
- `ContextItem` DTO — individual deduplicated item

## Config

Publish: `php artisan vendor:publish --tag="semantic-deduper-config"`

```php
return [
    'max_per_group'          => 3,
    'max_total'              => 12,
    'near_duplicate_threshold' => 0.92, // 1.0 = exact, 0.92 = very similar
];
```

## Basic Usage

```php
use IllumaLaw\SemanticDeduper\SemanticClusterer;

$items = [
    ['id' => 1, 'text' => '...', 'embedding' => [0.1, 0.2, ...], 'group' => 'articles'],
    ['id' => 2, 'text' => '...', 'embedding' => [0.1, 0.2, ...], 'group' => 'articles'],
    // ...
];

$clusterer = app(SemanticClusterer::class);

$result = $clusterer
    ->threshold(0.92)
    ->maxPerGroup(3)
    ->maxTotal(12)
    ->groupBy('group')
    ->embedKey('embedding')
    ->deduplicate($items);

foreach ($result->groups() as $group) {
    foreach ($group->items() as $item) {
        echo $item->get('text');
    }
}
```

## Fluent String Deduplication

```php
use IllumaLaw\SemanticDeduper\Facades\StringDeduper;

// Score two strings
$score = StringDeduper::score('Hello world', 'Hello World!');

// Deduplicate a collection of strings
$unique = collect($strings)->deduplicateStrings(threshold: 0.8);
```

## Text Chunking

```php
use IllumaLaw\SemanticDeduper\Chunker;

$chunks = Chunker::sliding(
    text: $longDocument,
    chunkSize: 512,
    overlap: 64,
);
```

## Array Merging

```php
use IllumaLaw\SemanticDeduper\ArrayMerger;

$merged = ArrayMerger::deepMerge($base, $override);
$absorbed = ArrayMerger::absorb($target, $source, fields: ['title', 'tags']);
```
