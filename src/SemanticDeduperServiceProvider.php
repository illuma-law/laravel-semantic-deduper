<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper;

use IllumaLaw\SemanticDeduper\Utils\StringSimilarity;
use Illuminate\Support\Collection;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SemanticDeduperServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('semantic-deduper')
            ->hasConfigFile();
    }

    public function packageBooted(): void
    {
        Collection::macro('dedupeFuzzy', function (string|callable $key, float $threshold = 90.0) {
            /** @var Collection<int, mixed> $this */
            $items = $this->all();
            $kept = [];

            foreach ($items as $item) {
                $normalizedValue = is_callable($key) ? $key($item) : data_get($item, $key);
                $isDuplicate = false;

                foreach ($kept as $keptItem) {
                    $keptNormalized = is_callable($key) ? $key($keptItem) : data_get($keptItem, $key);

                    $s1 = is_string($normalizedValue) ? $normalizedValue : (is_scalar($normalizedValue) ? (string) $normalizedValue : '');
                    $s2 = is_string($keptNormalized) ? $keptNormalized : (is_scalar($keptNormalized) ? (string) $keptNormalized : '');

                    if (StringSimilarity::score($s1, $s2) >= $threshold) {
                        $isDuplicate = true;
                        break;
                    }
                }

                if (! $isDuplicate) {
                    $kept[] = $item;
                }
            }

            /** @phpstan-ignore new.static */
            return new static($kept);
        });
    }
}
