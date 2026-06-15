<?php

namespace App\Factory;

use App\Entity\StockItem;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<StockItem>
 */
final class StockItemFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return StockItem::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'supplier' => 'test_supplier',
            'externalId' => 'EXT-' . uniqid(),
            'mpn' => 'MPN-' . uniqid(),
            'producerName' => 'Test Producer',
            'price' => 99.99,
            'quantity' => 10,
            'ean' => '5901234567890',
        ];
    }
}
