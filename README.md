# Laravel Semantic Deduper

[![Run Tests](https://github.com/illuma-law/laravel-semantic-deduper/actions/workflows/run-tests.yml/badge.svg)](https://github.com/illuma-law/laravel-semantic-deduper/actions/workflows/run-tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Blazing-fast, generic semantic deduplication for Laravel collections and arrays.

When dealing with search results or building context windows for LLMs (RAG pipelines), you often encounter near-duplicate content (e.g., an article snippet and its summary). Feeding redundant data to an LLM wastes tokens and degrades output quality. 

This package uses an optimized Cosine Similarity algorithm to identify and remove these "near-duplicates" based on their vector embeddings, ensuring your final dataset is highly diverse and relevant.

## Features

- **Blazing Fast**: The core cosine similarity math is highly optimized for PHP (strict float typing, loop unrolling, early returns).
- **Generic Structure**: Works flawlessly with associative arrays or Laravel Collections—it just needs an embedding vector.
- **Fluent Builder API**: Configure thresholds, group limits, and grouping logic on the fly.
- **Clean DTOs**: Returns your deduplicated data wrapped in structured, type-safe Data Transfer Objects (`GroupedContext`, `ContextGroup`, `ContextItem`).
- **Flexible Grouping**: Group results by categories, sources, or document types before deduplicating them.
- **Fuzzy String Deduplication**: New utilities for scoring string similarity (Levenshtein/SimilarText) and a fluent Collection macro.
- **Text Chunking**: Optimized sliding-window chunker for RAG pipelines.
- **Data Merging**: Utilities for deep merging associative arrays and absorbing fields between records.

## Installation

You can install the package via composer:

```bash
composer require illuma-law/laravel-semantic-deduper
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="semantic-deduper-config"
```

## Configuration

The published `config/semantic-deduper.php` file defines global fallback defaults:

```php
return [
    // Maximum items to keep per group
    'max_per_group' => 3,
    
    // Hard limit on total items across all groups combined
    'max_total' => 12,
    
    // The Cosine Similarity threshold. 
    // 1.0 means exact match. 0.95 means very similar. 
    // If a new item is >= 0.95 similar to an already accepted item, it is dropped.
    'near_duplicate_threshold' => 0.92, 
];
```

## Usage & Integration

### Basic Usage

Use the `SemanticClusterer` to group and deduplicate results in one go.

```php
use IllumaLaw\SemanticDeduper\SemanticClusterer;

$results = [
    ['id' => 1, 'source' => 'web', 'embedding' => [0.1, 0.2, 0.3]],
    ['id' => 2, 'source' => 'web', 'embedding' => [0.11, 0.19, 0.31]], // Very similar to #1
    ['id' => 3, 'source' => 'pdf', 'embedding' => [0.9, 0.8, -0.1]],
];

$grouped = SemanticClusterer::make()
    ->groupBy('source')
    ->threshold(0.95) // Anything 95% similar is dropped
    ->maxPerGroup(2)
    ->cluster($results);

foreach ($grouped->groups as $group) {
    echo "Group: " . $group->label . "\n";
    foreach ($group->items as $item) {
        // Only Item #1 and #3 will be kept
        echo " - Item ID: " . $item->get('id') . "\n";
    }
}
```

### Advanced Configuration

The fluent API allows deep customization of how the data is grouped and evaluated.

```php
use IllumaLaw\SemanticDeduper\SemanticClusterer;

$grouped = SemanticClusterer::make()
    ->maxPerGroup(5)
    ->maxTotal(20)
    ->threshold(0.90)
    ->embeddingKey('vector_data') // Tell it where your embeddings live (default: 'embedding')
    ->idKey('uuid')               // Default: 'id'
    ->groupBy(function ($row) {
        // Complex grouping closure
        return $row['category'] . '-' . $row['type'];
    })
    ->cluster($collection);
```

### Working with the DTOs

The `cluster()` method returns a `GroupedContext` object. This object provides numerous helpers to seamlessly extract your final dataset:

```php
// Check if all data was dropped
if ($grouped->isEmpty()) {
    // ...
}

// Get the final count of retained items
$total = $grouped->totalCount();

// Get a flat array of all retained ContextItem objects across all groups
$items = $grouped->allItems();

// Commonly used in RAG: Extract just the IDs so you can fetch the real Eloquent models
$modelIds = $grouped->collectIdentifiers('id');

$models = Article::whereIn('id', $modelIds)->get();
```

## Utilities

### Fuzzy Collection Deduplication

This package adds a `dedupeFuzzy` macro to Laravel Collections:

```php
$collection = collect([
    ['name' => 'John Doe'],
    ['name' => 'Jon Doe'], // fuzzy duplicate
    ['name' => 'Jane Smith'],
]);

$deduplicated = $collection->dedupeFuzzy('name', threshold: 85.0);
// Result contains 'John Doe' and 'Jane Smith'
```

### String Similarity

```php
use IllumaLaw\SemanticDeduper\Utils\StringSimilarity;

$score = StringSimilarity::score('Laravel', 'Laraval'); // ~85.7
$lev   = StringSimilarity::levenshteinScore('Laravel', 'Laraval');
```

### Text Chunking

Optimized for creating overlapping chunks for vector embeddings.

```php
use IllumaLaw\SemanticDeduper\Utils\TextChunker;

$chunks = TextChunker::chunk($longText, chunkSize: 500, overlap: 50);
```

### Data Merging

```php
use IllumaLaw\SemanticDeduper\Utils\DataMerger;

$merged = DataMerger::deepMerge($canonicalArray, $duplicateArray);

// Identify which fields in $duplicate can fill blanks in $canonical
$updates = DataMerger::identifyAbsorbableUpdates($canonical, $duplicate, ['email', 'phone']);
```

## Performance Note

While this package is highly optimized for PHP execution, computing cosine similarity in memory is O(N²) for each group. It is designed to run efficiently on result sets of up to a few thousand items (e.g., the raw output of a search engine query before sending to an LLM). Do not attempt to run it against your entire database.

## Testing

The package includes a comprehensive Pest test suite covering edge cases and mathematical precision.

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
