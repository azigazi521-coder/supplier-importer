<?php

declare(strict_types=1);

namespace App\Service\Stock\Parser;

use App\Dto\StockItemImportDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.supplier_processor')]
interface SupplierStockParserInterface
{
    /**
     * @return iterable<StockItemImportDto>
     */
    public function parse(string $filePath): iterable;


    public function getSupplierName(): string;
}
