<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Max Items Per Group
    |--------------------------------------------------------------------------
    |
    | The maximum number of items to retain within a single cluster/group
    | after semantic deduplication has been applied.
    |
    */
    'max_per_group' => (int) env('SEMANTIC_DEDUPER_MAX_PER_GROUP', 3),

    /*
    |--------------------------------------------------------------------------
    | Default Max Total Items
    |--------------------------------------------------------------------------
    |
    | The total maximum number of items to retain across all groups
    | in the final grouped context.
    |
    */
    'max_total' => (int) env('SEMANTIC_DEDUPER_MAX_TOTAL', 12),

    /*
    |--------------------------------------------------------------------------
    | Near Duplicate Threshold
    |--------------------------------------------------------------------------
    |
    | The cosine similarity threshold (0.0 to 1.0) above which two items
    | are considered near-duplicates and the lower-ranked one is removed.
    |
    */
    'near_duplicate_threshold' => (float) env('SEMANTIC_DEDUPER_THRESHOLD', 0.92),
];
