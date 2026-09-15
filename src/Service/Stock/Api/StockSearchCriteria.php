<?php

declare(strict_types=1);

namespace App\Service\Stock\Api;

final readonly class StockSearchCriteria
{
    public function __construct(
        public ?string $mpn,
        public ?string $ean,
    ) {}
}
