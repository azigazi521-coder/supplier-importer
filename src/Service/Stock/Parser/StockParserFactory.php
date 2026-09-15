<?php

declare(strict_types=1);

namespace App\Service\Stock\Parser;

use App\Service\Stock\Exception\StockImportInputException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class StockParserFactory implements SupplierParserRegistryInterface
{
    /** @var array<string, SupplierStockParserInterface> */
    private array $parsers = [];

    /**
     * @param iterable<SupplierStockParserInterface> $parsers
     */
    public function __construct(#[AutowireIterator('app.supplier_processor')] iterable $parsers)
    {
        foreach ($parsers as $parser) {
            if (!$parser instanceof SupplierStockParserInterface) {
                continue;
            }

            $supplierName = trim($parser->getSupplierName());

            if ('' === $supplierName) {
                throw new StockImportInputException('Supplier parser name cannot be empty.');
            }

            $key = strtolower($supplierName);

            if (isset($this->parsers[$key])) {
                throw new StockImportInputException(sprintf(
                    'Duplicate supplier parser name: "%s".',
                    $supplierName
                ));
            }

            $this->parsers[$key] = $parser;
        }
    }

    public function getParser(string $supplierName): SupplierStockParserInterface
    {
        $key = $this->normalizeSupplierName($supplierName);

        if (!isset($this->parsers[$key])) {
            throw new StockImportInputException(sprintf(
                'Unknown supplier: "%s". Available suppliers: %s',
                $supplierName,
                implode(', ', array_keys($this->parsers))
            ));
        }

        return $this->parsers[$key];
    }

    public function getCanonicalSupplierName(string $supplierName): string
    {
        return trim($this->getParser($supplierName)->getSupplierName());
    }

    private function normalizeSupplierName(string $supplierName): string
    {
        return strtolower(trim($supplierName));
    }
}
