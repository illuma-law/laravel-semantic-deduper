<?php

declare(strict_types=1);

namespace IllumaLaw\SemanticDeduper;

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
}
