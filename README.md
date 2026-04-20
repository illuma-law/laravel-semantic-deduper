# Laravel Semantic Deduper

[![Run Tests](https://github.com/illuma-law/laravel-semantic-deduper/actions/workflows/run-tests.yml/badge.svg)](https://github.com/illuma-law/laravel-semantic-deduper/actions/workflows/run-tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

**Laravel Semantic Deduper** is a blazing-fast, generic package for deduplicating arrays and collections of data based on semantic similarity (embeddings). It uses an optimized cosine similarity implementation to identify and remove near-duplicate items within groups, ensuring high-quality, diverse results for LLM prompts, search results, and more.

## TL;DR

```php
use IllumaLaw\SemanticDeduper\SemanticClusterer;

// Group results by source and remove semantically similar duplicates
$grouped = SemanticClusterer::make()
    ->groupBy('source')
    ->threshold(0.95)
    ->cluster($results);

foreach ($grouped->groups as $group) {
    echo "Source: {$group->label}\n";
    foreach ($group->items as $item) {
        echo " - {$item->get('title')}\n";
    }
}
```

## Features

- 🚀 **Blazing Fast**: Optimized cosine similarity with strict typing and early returns.
- 🏗️ **Generic & Flexible**: Works with any data structure (associative arrays or collections) that contains embeddings.
- 🌊 **Fluent Builder**: Configure thresholds, limits, and grouping logic on the fly.
- 📦 **Clean DTOs**: Returns structured `GroupedContext`, `ContextGroup`, and `ContextItem` objects.
- 🛠️ **Configurable**: Define global defaults for thresholds and limits.
- 🧪 **Thoroughly Tested**: Extensive Pest v4 test suite covering edge cases and performance.

## Installation

You can install the package via composer:

```bash
composer require illuma-law/laravel-semantic-deduper
```

The service provider will automatically register itself. You can publish the config file with:

```bash
php artisan vendor:publish --tag="semantic-deduper-config"
```

## Configuration

The published `config/semantic-deduper.php` file allows you to set global defaults:

```php
return [
    'max_per_group' => 3,
    'max_total' => 12,
    'near_duplicate_threshold' => 0.92, // 1.0 is exact match, < 1.0 for near-duplicates
];
```

## Usage

### Basic Usage

The `SemanticClusterer` allows you to group and deduplicate results in one go.

```php
use IllumaLaw\SemanticDeduper\SemanticClusterer;

$results = [
    ['id' => 1, 'source' => 'web', 'embedding' => [0.1, 0.2, ...]],
    ['id' => 2, 'source' => 'web', 'embedding' => [0.11, 0.19, ...]], // Near duplicate
    ['id' => 3, 'source' => 'pdf', 'embedding' => [0.9, 0.8, ...]],
];

$grouped = SemanticClusterer::make()
    ->groupBy('source')
    ->threshold(0.95)
    ->maxPerGroup(1)
    ->cluster($results);

foreach ($grouped->groups as $group) {
    echo "Group: " . $group->label . "\n";
    foreach ($group->items as $item) {
        echo " - Item ID: " . $item->get('id') . "\n";
    }
}
```

### Fluent Configuration

You can configure the clusterer using a fluent API:

```php
$grouped = SemanticClusterer::make()
    ->maxPerGroup(5)
    ->maxTotal(20)
    ->threshold(0.90)
    ->embeddingKey('vector') // Custom key for embeddings
    ->idKey('uuid')         // Custom key for identifiers
    ->groupBy(fn($row) => $row['category'] . '-' . $row['type']) // Custom grouping logic
    ->cluster($collection);
```

### Data Transfer Objects (DTOs)

The package returns a `GroupedContext` object which provides helper methods for interacting with the results:

- `$grouped->isEmpty()`: Check if no items were retained.
- `$grouped->totalCount()`: Get the total number of items across all groups.
- `$grouped->allItems()`: Returns a flat list of `ContextItem` objects.
- `$grouped->collectIdentifiers('id')`: Returns a unique list of identifiers (e.g., database IDs).

## Performance

The core of the package is an optimized `cosineSimilarity` function. It is designed to be as efficient as possible in PHP by:
- Using strict float typing.
- Minimizing function calls inside loops.
- Providing early returns for empty or zero-magnitude vectors.

## Testing

The package includes a comprehensive test suite powered by Pest v4.

```bash
composer test
```

## Credits

- [illuma-law](https://github.com/illuma-law)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
