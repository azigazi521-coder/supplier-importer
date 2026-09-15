<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock;

use App\Dto\StockItemImportDto;
use App\Service\Stock\Exception\StockImportException;
use App\Service\Stock\StockItemBulkUpserter;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class StockItemBulkUpserterTest extends TestCase
{
    public function testEmptyBatchReturnsZeroWithoutExecutingSql(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('executeStatement');
        $connection->expects(self::never())->method('beginTransaction');

        $upserter = new StockItemBulkUpserter($connection);

        self::assertSame(0, $upserter->upsertBatch('trah', [], 1));
    }

    public function testBuildsOneMultiRowStatementWithAllParameters(): void
    {
        $connection = $this->createMock(Connection::class);
        $capturedSql = null;
        $capturedParameters = null;

        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $parameters) use (&$capturedSql, &$capturedParameters): int {
                $capturedSql = $sql;
                $capturedParameters = $parameters;

                return 2;
            });
        $connection->expects(self::once())->method('commit');

        $items = [
            $this->createDto('EXT-001', 'MPN-001', '10.50', 5),
            $this->createDto('EXT-002', 'MPN-002', '20.75', 8),
        ];

        $upserter = new StockItemBulkUpserter($connection);

        self::assertSame(2, $upserter->upsertBatch('trah', $items, 3));
        self::assertIsString($capturedSql);
        self::assertIsArray($capturedParameters);
        self::assertSame(14, count($capturedParameters));
        self::assertSame(2, substr_count($capturedSql, '(:supplier_'));
        self::assertStringContainsString('ON DUPLICATE KEY UPDATE', $capturedSql);
        self::assertStringContainsString('ean = incoming.ean', $capturedSql);
        self::assertStringContainsString('mpn = incoming.mpn', $capturedSql);
        self::assertStringContainsString('producer_name = incoming.producer_name', $capturedSql);
        self::assertStringContainsString('price = incoming.price', $capturedSql);
        self::assertStringContainsString('quantity = incoming.quantity', $capturedSql);
        self::assertSame('trah', $capturedParameters['supplier_0']);
        self::assertSame('EXT-001', $capturedParameters['external_id_0']);
        self::assertSame('MPN-001', $capturedParameters['mpn_0']);
        self::assertSame('20.75', $capturedParameters['price_1']);
        self::assertSame(8, $capturedParameters['quantity_1']);
    }

    public function testRollsBackAndThrowsContextualExceptionWhenSqlFails(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())
            ->method('executeStatement')
            ->willThrowException(new \RuntimeException('SQL failed'));
        $connection->expects(self::once())->method('isTransactionActive')->willReturn(true);
        $connection->expects(self::once())->method('rollBack');
        $connection->expects(self::never())->method('commit');

        $upserter = new StockItemBulkUpserter($connection);

        try {
            $upserter->upsertBatch('lorotom', [$this->createDto('EXT-FAIL', 'MPN-FAIL', '1.00', 1)], 7);
            self::fail('Expected StockImportException was not thrown.');
        } catch (StockImportException $exception) {
            self::assertStringContainsString('lorotom', $exception->getMessage());
            self::assertStringContainsString('batch 7', $exception->getMessage());
            self::assertSame('SQL failed', $exception->getPrevious()->getMessage());
        }
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
