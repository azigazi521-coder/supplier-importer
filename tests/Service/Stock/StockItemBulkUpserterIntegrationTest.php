<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock;

use App\Dto\StockItemImportDto;
use App\Service\Stock\StockItemBulkUpserter;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StockItemBulkUpserterIntegrationTest extends KernelTestCase
{
    private const SUPPLIER = 'bulk_test_supplier';

    private Connection $connection;
    private StockItemBulkUpserter $upserter;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = static::getContainer()->get(Connection::class);
        $this->connection->executeStatement(
            'DELETE FROM stock_items WHERE supplier = :supplier',
            ['supplier' => self::SUPPLIER],
        );
        $this->upserter = new StockItemBulkUpserter($this->connection);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->executeStatement(
                'DELETE FROM stock_items WHERE supplier = :supplier',
                ['supplier' => self::SUPPLIER],
            );
        }

        parent::tearDown();
    }

    public function testInsertsSeveralRecordsAndUpdatesExistingUniqueKey(): void
    {
        $items = [
            $this->createDto('EXT-001', 'MPN-001', '10.50', 5),
            $this->createDto('EXT-002', 'MPN-002', '20.75', 8),
        ];

        self::assertSame(2, $this->upserter->upsertBatch(self::SUPPLIER, $items, 1));
        self::assertSame(2, $this->countSupplierRows());

        $updatedItem = $this->createDto('EXT-001', 'MPN-001-UPDATED', '99.99', 42);
        self::assertSame(1, $this->upserter->upsertBatch(self::SUPPLIER, [$updatedItem], 2));

        $row = $this->connection->fetchAssociative(
            'SELECT external_id, mpn, price, quantity FROM stock_items WHERE supplier = :supplier AND external_id = :external_id',
            ['supplier' => self::SUPPLIER, 'external_id' => 'EXT-001'],
        );

        self::assertSame(2, $this->countSupplierRows());
        self::assertSame('EXT-001', $row['external_id']);
        self::assertSame('MPN-001-UPDATED', $row['mpn']);
        self::assertSame('99.99', $row['price']);
        self::assertSame(42, (int) $row['quantity']);
    }

    public function testRollsBackWholeBatchWhenOneRecordIsInvalid(): void
    {
        $validItem = $this->createDto('EXT-VALID', 'MPN-VALID', '10.00', 1);
        $invalidItem = $this->createDto(str_repeat('X', 101), 'MPN-INVALID', '20.00', 2);

        $this->expectExceptionMessage('Failed to import batch 3');
        try {
            $this->upserter->upsertBatch(self::SUPPLIER, [$validItem, $invalidItem], 3);
        } finally {
            self::assertSame(0, $this->countSupplierRows());
        }
    }

    private function countSupplierRows(): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM stock_items WHERE supplier = :supplier',
            ['supplier' => self::SUPPLIER],
        );
    }

    private function createDto(string $externalId, string $mpn, string $price, int $quantity): StockItemImportDto
    {
        return new StockItemImportDto(
            externalId: $externalId,
            ean: '5901234567890',
            mpn: $mpn,
            producerName: 'Test Producer',
            price: $price,
            quantity: $quantity,
        );
    }
}
