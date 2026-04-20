<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Data;

final readonly class ContextGroup
{
    /**
     * @param  string  $label  Human-readable label for the group.
     * @param  list<ContextItem>  $items
     * @param  array<string, mixed>  $metadata  Any additional metadata about the group.
     */
    public function __construct(
        public string $label,
        public array $items,
        public array $metadata = [],
    ) {}
}
