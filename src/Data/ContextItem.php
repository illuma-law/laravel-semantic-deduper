<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Data;

final readonly class ContextItem
{
    /**
     * @param  array<string, mixed>  $payload  The original associative array row.
     */
    public function __construct(
        public array $payload,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }

    public function __get(string $name): mixed
    {
        return $this->payload[$name] ?? null;
    }
}
