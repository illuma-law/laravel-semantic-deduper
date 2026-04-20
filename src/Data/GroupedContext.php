<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper\Data;

final readonly class GroupedContext
{
    /**
     * @param  list<ContextGroup>  $groups
     */
    public function __construct(
        public array $groups,
    ) {}

    public function isEmpty(): bool
    {
        return $this->groups === [];
    }

    /**
     * @return list<ContextItem>
     */
    public function allItems(): array
    {
        $all = [];
        foreach ($this->groups as $group) {
            foreach ($group->items as $item) {
                $all[] = $item;
            }
        }

        return $all;
    }

    /**
     * @return list<mixed>
     */
    public function collectIdentifiers(string $idKey = 'id'): array
    {
        $ids = [];
        foreach ($this->allItems() as $item) {
            $id = $item->get($idKey);
            if ($id !== null && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function totalCount(): int
    {
        return array_sum(array_map(static fn (ContextGroup $g): int => count($g->items), $this->groups));
    }
}
