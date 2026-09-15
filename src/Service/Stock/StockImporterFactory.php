<?php

declare(strict_types=1);

namespace App\Service\Stock;

final class StockImporterFactory
{
    public function __construct(
        private readonly LegacyStockImporter $legacyStockImporter,
        private readonly MultilineStockImporter $multilineStockImporter,
    ) {}

    public function create(StockImportMode $mode): StockImporterInterface
    {
        return match ($mode) {
            StockImportMode::LEGACY => $this->legacyStockImporter,
            StockImportMode::MULTILINE => $this->multilineStockImporter,
        };
    }
}
