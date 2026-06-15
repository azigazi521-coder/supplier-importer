<?php

namespace App\Dto;

readonly class StockItemImportDto
{
    public function __construct(
        public string $externalId,
        public ?string $ean,
        public string $mpn,
        public string $producerName,
        public string $price,
        public int $quantity
    ) {}
}
