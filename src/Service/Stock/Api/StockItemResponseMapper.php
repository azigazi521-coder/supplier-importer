<?php

declare(strict_types=1);

namespace App\Service\Stock\Api;

use App\Entity\StockItem;

final class StockItemResponseMapper
{
    /**
     * @return array<string, int|float|string|null>
     */
    public function map(StockItem $item): array
    {
        return [
            'id' => $item->getId(),
            'ean' => $item->getEan(),
            'mpn' => $item->getMpn(),
            'producer_name' => $item->getProducerName(),
            'external_id' => $item->getExternalId(),
            'price' => (float) $item->getPrice(),
            'quantity' => $item->getQuantity(),
            'supplier' => $item->getSupplier(),
        ];
    }
}
