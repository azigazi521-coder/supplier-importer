<?php

declare(strict_types=1);

namespace App\Service\Stock;

use App\Service\Stock\Exception\StockImportInputException;
use App\Service\Stock\Parser\SupplierParserRegistryInterface;

class StockImporter
{
    public function __construct(
        private readonly SupplierParserRegistryInterface $parserRegistry,
        private readonly StockItemUpserter $stockItemUpserter,
        private readonly StockImportBatchProcessor $batchProcessor,
    ) {}

    public function import(string $filePath, string $supplierName): int
    {
        $this->validateFile($filePath);

        $canonicalSupplierName = $this->parserRegistry->getCanonicalSupplierName($supplierName);
        $parser = $this->parserRegistry->getParser($canonicalSupplierName);
        $stockDataIterator = $parser->parse($filePath);
        $processedCount = 0;

        foreach ($stockDataIterator as $dto) {
            $this->stockItemUpserter->upsert($canonicalSupplierName, $dto);

            $processedCount++;
            $this->batchProcessor->flushIfNeeded($processedCount);
        }

        $this->batchProcessor->finish();

        return $processedCount;
    }

    private function validateFile(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new StockImportInputException(sprintf(
                'File not found or unreadable: %s',
                $filePath
            ));
        }
    }
}
