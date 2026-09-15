<?php

declare(strict_types=1);

namespace App\Service\Stock;

use App\Dto\StockItemImportDto;
use App\Service\Stock\Exception\StockImportException;
use Doctrine\DBAL\Connection;

final class StockItemBulkUpserter
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * @param list<StockItemImportDto> $items
     */
    public function upsertBatch(string $supplierName, array $items, int $batchNumber): int
    {
        if ([] === $items) {
            return 0;
        }

        $valueGroups = [];
        $parameters = [];

        foreach ($items as $index => $item) {
            $valueGroups[] = sprintf(
                '(:supplier_%1$d, :external_id_%1$d, :ean_%1$d, :mpn_%1$d, :producer_name_%1$d, :price_%1$d, :quantity_%1$d)',
                $index,
            );
            $parameters["supplier_$index"] = $supplierName;
            $parameters["external_id_$index"] = $item->externalId;
            $parameters["ean_$index"] = $item->ean;
            $parameters["mpn_$index"] = $item->mpn;
            $parameters["producer_name_$index"] = $item->producerName;
            $parameters["price_$index"] = $item->price;
            $parameters["quantity_$index"] = $item->quantity;
        }

        $sql = sprintf(
            <<<'SQL'
INSERT INTO stock_items (
    supplier,
    external_id,
    ean,
    mpn,
    producer_name,
    price,
    quantity
) VALUES %s AS incoming
ON DUPLICATE KEY UPDATE
    ean = incoming.ean,
    mpn = incoming.mpn,
    producer_name = incoming.producer_name,
    price = incoming.price,
    quantity = incoming.quantity
SQL,
            implode(', ', $valueGroups),
        );

        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement($sql, $parameters);
            $this->connection->commit();
        } catch (\Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw new StockImportException(sprintf(
                'Failed to import batch %d for supplier "%s": %s',
                $batchNumber,
                $supplierName,
                $exception->getMessage(),
            ), 0, $exception);
        }

        return count($items);
    }
}
