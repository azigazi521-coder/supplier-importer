<?php

declare(strict_types=1);

namespace App\Service\Stock;

use App\Service\Stock\Exception\StockImportInputException;
use App\Service\Stock\Parser\SupplierParserRegistryInterface;

abstract class StockImporter implements StockImporterInterface
{
    public function __construct(
        private readonly SupplierParserRegistryInterface $parserRegistry,
    ) {}

    /**
     * @return array{0: iterable, 1: string}
     */
    protected function prepareImport(string $filePath, string $supplierName): array
    {
        $this->validateFile($filePath);

        $canonicalSupplierName = $this->parserRegistry->getCanonicalSupplierName($supplierName);
        $parser = $this->parserRegistry->getParser($canonicalSupplierName);
        return [$parser->parse($filePath), $canonicalSupplierName];
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
