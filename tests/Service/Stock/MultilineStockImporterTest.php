<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock;

use App\Dto\StockItemImportDto;
use App\Service\Stock\MultilineStockImporter;
use App\Service\Stock\Parser\SupplierParserRegistryInterface;
use App\Service\Stock\Parser\SupplierStockParserInterface;
use App\Service\Stock\StockItemBulkUpserter;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MultilineStockImporterTest extends TestCase
{
    private const SUPPLIER = 'trah';
    private const FILE_PATH = __DIR__ . '/multiline-import-fixture.csv';

    private SupplierParserRegistryInterface&MockObject $parserRegistry;
    private SupplierStockParserInterface&MockObject $parser;
    private Connection&MockObject $connection;
    private MultilineStockImporter $importer;

    protected function setUp(): void
    {
        file_put_contents(self::FILE_PATH, 'fixture');

        $this->parserRegistry = $this->createMock(SupplierParserRegistryInterface::class);
        $this->parser = $this->createMock(SupplierStockParserInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $this->parserRegistry
            ->method('getCanonicalSupplierName')
            ->with(self::SUPPLIER)
            ->willReturn(self::SUPPLIER);
        $this->parserRegistry
            ->method('getParser')
            ->with(self::SUPPLIER)
            ->willReturn($this->parser);

        $this->importer = new MultilineStockImporter(
            new StockItemBulkUpserter($this->connection),
            $this->parserRegistry,
        );
    }

    protected function tearDown(): void
    {
        if (file_exists(self::FILE_PATH)) {
            unlink(self::FILE_PATH);
        }
    }

    #[DataProvider('batchSizeProvider')]
    public function testProcessesBatchSizesAndReturnsCount(int $itemCount, int $expectedBatchCount): void
    {
        $this->parser->method('parse')->willReturn($this->items($itemCount));
        $this->expectBulkUpserts($expectedBatchCount);

        self::assertSame(
            $itemCount,
            $this->importer->import(self::FILE_PATH, self::SUPPLIER),
        );
    }

    public static function batchSizeProvider(): iterable
    {
        yield 'empty input' => [0, 0];
        yield 'one item' => [1, 1];
        yield '49 items' => [49, 1];
        yield 'full batch' => [50, 1];
        yield 'full batch and remainder' => [51, 2];
        yield 'two full batches' => [100, 2];
    }

    public function testDoesNotLoadWholeIterableBeforeFirstBatchIsPersisted(): void
    {
        $yieldedCount = 0;
        $this->parser->method('parse')->willReturn($this->items(100, $yieldedCount));

        $executeCount = 0;

        $this->connection
            ->expects(self::exactly(2))
            ->method('beginTransaction');
        $this->connection
            ->expects(self::exactly(2))
            ->method('commit');
        $this->connection
            ->expects(self::exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(function () use (&$executeCount, &$yieldedCount): int {
                $executeCount++;

                if (1 === $executeCount) {
                    self::assertSame(50, $yieldedCount);
                }

                return 50;
            });

        self::assertSame(100, $this->importer->import(self::FILE_PATH, self::SUPPLIER));
        self::assertSame(100, $yieldedCount);
    }

    private function expectBulkUpserts(int $batchCount): void
    {
        $this->connection
            ->expects(self::exactly($batchCount))
            ->method('beginTransaction');
        $this->connection
            ->expects(self::exactly($batchCount))
            ->method('commit');
        $this->connection
            ->expects(self::exactly($batchCount))
            ->method('executeStatement')
            ->willReturn(50);
    }

    /**
     * @return iterable<int, StockItemImportDto>
     */
    private function items(int $count, ?int &$yieldedCount = null): iterable
    {
        for ($index = 1; $index <= $count; $index++) {
            if (null !== $yieldedCount) {
                $yieldedCount++;
            }

            yield new StockItemImportDto(
                externalId: sprintf('EXT-%03d', $index),
                ean: '5901234567890',
                mpn: sprintf('MPN-%03d', $index),
                producerName: 'Test Producer',
                price: '10.50',
                quantity: 5,
            );
        }
    }
}
