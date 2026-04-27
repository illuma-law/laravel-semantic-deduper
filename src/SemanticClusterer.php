<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper;

use IllumaLaw\SemanticDeduper\Data\ContextGroup;
use IllumaLaw\SemanticDeduper\Data\ContextItem;
use IllumaLaw\SemanticDeduper\Data\GroupedContext;
use Illuminate\Support\Collection;

final class SemanticClusterer
{
    protected int $maxPerGroup;

    protected int $maxTotal;

    protected float $threshold;

    protected string $embeddingKey = 'embedding';

    protected string $idKey = 'id';

    /** @var (callable(array<string, mixed>): string)|string */
    protected $groupKey = 'group';

    public function __construct(
        ?int $maxPerGroup = null,
        ?int $maxTotal = null,
        ?float $threshold = null
    ) {
        /** @var mixed $configMaxPerGroup */
        $configMaxPerGroup = config('semantic-deduper.max_per_group', 3);
        $this->maxPerGroup = $maxPerGroup ?? (is_numeric($configMaxPerGroup) ? (int) $configMaxPerGroup : 3);

        /** @var mixed $configMaxTotal */
        $configMaxTotal = config('semantic-deduper.max_total', 12);
        $this->maxTotal = $maxTotal ?? (is_numeric($configMaxTotal) ? (int) $configMaxTotal : 12);

        /** @var mixed $configThreshold */
        $configThreshold = config('semantic-deduper.near_duplicate_threshold', 0.92);
        $this->threshold = $threshold ?? (is_numeric($configThreshold) ? (float) $configThreshold : 0.92);
    }

    public static function make(?int $maxPerGroup = null, ?int $maxTotal = null, ?float $threshold = null): self
    {
        return new self($maxPerGroup, $maxTotal, $threshold);
    }

    public static function distanceToSimilarity(float $distance): float
    {
        return max(0.0, 1.0 - $distance);
    }

    public static function similarityToDistance(float $similarity): float
    {
        return max(0.0, 1.0 - $similarity);
    }

    public function maxPerGroup(int $maxPerGroup): self
    {
        $this->maxPerGroup = $maxPerGroup;

        return $this;
    }

    public function maxTotal(int $maxTotal): self
    {
        $this->maxTotal = $maxTotal;

        return $this;
    }

    public function threshold(float $threshold): self
    {
        $this->threshold = $threshold;

        return $this;
    }

    public function embeddingKey(string $key): self
    {
        $this->embeddingKey = $key;

        return $this;
    }

    public function idKey(string $key): self
    {
        $this->idKey = $key;

        return $this;
    }

    /**
     * @param  (callable(array<string, mixed>): string)|string  $key
     */
    public function groupBy(callable|string $key): self
    {
        $this->groupKey = $key;

        return $this;
    }

    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $results
     */
    public function cluster(Collection|array $results): GroupedContext
    {
        $items = $results instanceof Collection ? $results->all() : $results;

        if (empty($items)) {
            return new GroupedContext([]);
        }

        /** @var array<string, list<array<string, mixed>>> $buckets */
        $buckets = [];

        foreach ($items as $row) {
            $key = $this->resolveGroupKey($row);
            $buckets[$key][] = $row;
        }

        $groups = [];
        $totalKept = 0;

        foreach ($buckets as $label => $rows) {
            if ($totalKept >= $this->maxTotal) {
                break;
            }

            $dedupedRows = $this->deduplicate($rows);
            $cappedRows = array_slice($dedupedRows, 0, $this->maxPerGroup);

            $remaining = $this->maxTotal - $totalKept;
            $finalRows = array_slice($cappedRows, 0, $remaining);

            $contextItems = array_map(
                static fn (array $row): ContextItem => new ContextItem($row),
                $finalRows
            );

            $groups[] = new ContextGroup(
                label: $label,
                items: $contextItems
            );

            $totalKept += count($contextItems);
        }

        return new GroupedContext($groups);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function deduplicate(array $rows): array
    {
        $kept = [];
        /** @var list<list<float>> $keptEmbeddings */
        $keptEmbeddings = [];

        foreach ($rows as $row) {
            /** @var list<float>|null $embedding */
            $embedding = $row[$this->embeddingKey] ?? null;

            if (! is_array($embedding) || empty($embedding)) {
                $kept[] = $row;

                continue;
            }

            $isDuplicate = false;

            foreach ($keptEmbeddings as $keptEmb) {
                if ($this->cosineSimilarity($embedding, $keptEmb) >= $this->threshold) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (! $isDuplicate) {
                $kept[] = $row;
                $keptEmbeddings[] = $embedding;
            }
        }

        return $kept;
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $countA = count($a);
        $countB = count($b);
        $len = $countA < $countB ? $countA : $countB;

        if ($len === 0) {
            return 0.0;
        }

        for ($i = 0; $i < $len; $i++) {
            $va = (float) $a[$i];
            $vb = (float) $b[$i];

            $dot += $va * $vb;
            $normA += $va * $va;
            $normB += $vb * $vb;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function resolveGroupKey(array $row): string
    {
        if (is_string($this->groupKey)) {
            $val = $row[$this->groupKey] ?? 'unknown';

            return is_string($val) ? $val : (is_scalar($val) ? (string) $val : 'unknown');
        }

        /** @var string $result */
        $result = ($this->groupKey)($row);

        return $result;
    }
}
