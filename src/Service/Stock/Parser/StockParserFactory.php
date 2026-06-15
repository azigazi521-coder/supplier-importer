<?php

declare(strict_types=1);

namespace App\Service\Stock\Parser;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class StockParserFactory
{
    /** @var array<string, SupplierStockParserInterface> */
    private array $parsers = [];

    /**
     * @param iterable<SupplierStockParserInterface> $parsers
     */
    public function __construct(#[AutowireIterator('app.supplier_processor')] iterable $parsers)
    {
        foreach ($parsers as $parser) {
            if ($parser instanceof SupplierStockParserInterface) {
                $this->parsers[strtolower($parser->getSupplierName())] = $parser;
            }
        }
    }

    public function getParser(string $supplierName): SupplierStockParserInterface
    {

        $key = strtolower($supplierName);

        if (!isset($this->parsers[$supplierName])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown supplier: "%s". Available suppliers: %s',
                $supplierName,
                implode(', ', array_keys($this->parsers))
            ));
        }

        return $this->parsers[$key];
    }
}
