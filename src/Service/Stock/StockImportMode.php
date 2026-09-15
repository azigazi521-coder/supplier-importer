<?php

declare(strict_types=1);

namespace App\Service\Stock;

enum StockImportMode: string
{
    case LEGACY = 'legacy';
    case MULTILINE = 'multiline';
}
