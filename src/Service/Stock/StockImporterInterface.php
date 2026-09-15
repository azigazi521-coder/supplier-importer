<?php

declare(strict_types=1);

namespace App\Service\Stock;

interface StockImporterInterface
{
    public function import(string $filePath, string $supplierName): int;
}
