<?php

declare(strict_types=1);

namespace App\Service\Stock;

use App\Service\Stock\Parser\SupplierParserRegistryInterface;

final class LegacyStockImporter extends StockImporter
{
    public function __construct(
        private readonly StockItemUpserter $stockItemUpserter,
        private readonly StockImportBatchProcessor $batchProcessor,
        SupplierParserRegistryInterface $parserRegistry,
    ) {
        parent::__construct($parserRegistry);
    }

    public function import(string $filePath, string $supplierName): int
    {
        [$stockDataIterator, $canonicalSupplierName] = $this->prepareImport($filePath, $supplierName);
        $processedCount = 0;

        foreach ($stockDataIterator as $dto) {
            $this->stockItemUpserter->upsert($canonicalSupplierName, $dto);
            $processedCount++;
            $this->batchProcessor->flushIfNeeded($processedCount);
        }

        $this->batchProcessor->finish();

        return $processedCount;
    }
}
