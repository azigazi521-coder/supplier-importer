<?php

declare(strict_types=1);

namespace App\Service\Stock\Parser;

interface SupplierParserRegistryInterface
{
    public function getParser(string $supplierName): SupplierStockParserInterface;

    public function getCanonicalSupplierName(string $supplierName): string;
}
