<?php

declare(strict_types=1);

namespace App\Service\Stock;

use App\Service\Stock\Parser\SupplierParserRegistryInterface;

final class MultilineStockImporter extends StockImporter
{
    public function __construct(
        private readonly StockItemBulkUpserter $stockItemBulkUpserter,
        SupplierParserRegistryInterface $parserRegistry,
    ) {
        parent::__construct($parserRegistry);
    }

    public function import(string $filePath, string $supplierName): int
    {
        [$stockDataIterator, $canonicalSupplierName] = $this->prepareImport($filePath, $supplierName);
        $batch = [];
        $batchNumber = 0;
        $processedCount = 0;

        foreach ($stockDataIterator as $dto) {
            $batch[] = $dto;
            $processedCount++;

            if (StockImportBatchConfiguration::SIZE === count($batch)) {
                $this->stockItemBulkUpserter->upsertBatch(
                    $canonicalSupplierName,
                    $batch,
                    ++$batchNumber,
                );
                $batch = [];
            }
        }

        if ([] !== $batch) {
            $this->stockItemBulkUpserter->upsertBatch(
                $canonicalSupplierName,
                $batch,
                ++$batchNumber,
            );
        }

        return $processedCount;
    }
}
