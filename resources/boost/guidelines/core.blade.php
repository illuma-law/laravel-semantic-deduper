# illuma-law/laravel-semantic-deduper

Deduplicate arrays and collections using semantic similarity (embeddings).

## Usage

### Semantic Clustering

```php
use IllumaLaw\SemanticDeduper\SemanticClusterer;

$grouped = SemanticClusterer::make()
    ->groupBy('source')
    ->threshold(0.95) // Similarity threshold (1.0 = exact)
    ->maxPerGroup(3)
    ->cluster($results);

foreach ($grouped->groups as $group) {
    // $group->label
    foreach ($group->items as $item) {
        // $item->get('title')
    }
}
```

### Fluent Configuration

```php
$grouped = SemanticClusterer::make()
    ->maxTotal(20)
    ->embeddingKey('vector') // Custom key for embeddings
    ->idKey('uuid')         // Custom key for IDs
    ->groupBy(fn($row) => $row['category'])
    ->cluster($collection);
```

## DTOs

- **GroupedContext**: `groups`, `totalCount()`, `isEmpty()`, `allItems()`.
- **ContextGroup**: `label`, `items`.
- **ContextItem**: `data`, `get(key)`.

## Configuration

Publish config: `php artisan vendor:publish --tag="semantic-deduper-config"`

Global defaults in `config/semantic-deduper.php`:
- `max_per_group`: Default 3
- `max_total`: Default 12
- `near_duplicate_threshold`: Default 0.92
