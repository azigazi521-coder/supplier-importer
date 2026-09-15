<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock;

use App\Dto\StockItemImportDto;
use App\Entity\StockItem;
use App\Repository\StockItemRepository;
use App\Service\Stock\Exception\StockImportInputException;
use App\Service\Stock\LegacyStockImporter;
use App\Service\Stock\Parser\SupplierParserRegistryInterface;
use App\Service\Stock\Parser\SupplierStockParserInterface;
use App\Service\Stock\StockImportBatchProcessor;
use App\Service\Stock\StockItemUpserter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LegacyStockImporterTest extends TestCase
{
    private const SUPPLIER = 'trah';
    private const FILE_PATH = __DIR__ . '/legacy-import-fixture.csv';

    private SupplierParserRegistryInterface&MockObject $parserRegistry;
    private SupplierStockParserInterface&MockObject $parser;
    private StockItemRepository&MockObject $stockItemRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private LegacyStockImporter $importer;

    protected function setUp(): void
    {
        file_put_contents(self::FILE_PATH, 'fixture');

        $this->parserRegistry = $this->createMock(SupplierParserRegistryInterface::class);
        $this->parser = $this->createMock(SupplierStockParserInterface::class);
        $this->stockItemRepository = $this->createMock(StockItemRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->parserRegistry
            ->method('getCanonicalSupplierName')
            ->with(self::SUPPLIER)
            ->willReturn(self::SUPPLIER);
        $this->parserRegistry
            ->method('getParser')
            ->with(self::SUPPLIER)
            ->willReturn($this->parser);
        $stockItemUpserter = new StockItemUpserter(
            $this->entityManager,
            $this->stockItemRepository,
        );
        $batchProcessor = new StockImportBatchProcessor($this->entityManager);

        $this->importer = new LegacyStockImporter(
            $stockItemUpserter,
            $batchProcessor,
            $this->parserRegistry,
        );
    }

    protected function tearDown(): void
    {
        if (file_exists(self::FILE_PATH)) {
            unlink(self::FILE_PATH);
        }
    }

    public function testEmptyFileReturnsZeroAndFinishesBatchProcessor(): void
    {
        $this->parser->method('parse')->willReturn([]);
        $this->expectPersistenceCalls(0);
        $this->entityManager->expects(self::once())->method('flush');
        $this->entityManager->expects(self::once())->method('clear');

        $this->assertSame(0, $this->importer->import(self::FILE_PATH, self::SUPPLIER));
    }

    public function testProcessesDtosOneByOneAndReturnsCount(): void
    {
        $items = [$this->createDto('EXT-001'), $this->createDto('EXT-002')];
        $this->parser->method('parse')->willReturn($items);
        $this->expectPersistenceCalls(2);
        $this->entityManager->expects(self::once())->method('flush');
        $this->entityManager->expects(self::once())->method('clear');

        $this->assertSame(2, $this->importer->import(self::FILE_PATH, self::SUPPLIER));
    }

    public function testFlushesFullBatchAndFinishesWithFinalFlush(): void
    {
        $items = array_map(
            fn (int $index): StockItemImportDto => $this->createDto(sprintf('EXT-%03d', $index)),
            range(1, 50),
        );
        $this->parser->method('parse')->willReturn($items);
        $this->expectPersistenceCalls(50);
        $this->entityManager->expects(self::exactly(2))->method('flush');
        $this->entityManager->expects(self::exactly(2))->method('clear');

        $this->assertSame(50, $this->importer->import(self::FILE_PATH, self::SUPPLIER));
    }

    public function testFlushesLastIncompleteBatch(): void
    {
        $items = array_map(
            fn (int $index): StockItemImportDto => $this->createDto(sprintf('EXT-%03d', $index)),
            range(1, 51),
        );
        $this->parser->method('parse')->willReturn($items);
        $this->expectPersistenceCalls(51);
        $this->entityManager->expects(self::exactly(2))->method('flush');
        $this->entityManager->expects(self::exactly(2))->method('clear');

        $this->assertSame(51, $this->importer->import(self::FILE_PATH, self::SUPPLIER));
    }

    public function testPropagatesParserException(): void
    {
        $exception = new \RuntimeException('Parser failed');
        $this->parser->method('parse')->willThrowException($exception);

        $this->expectExceptionObject($exception);
        $this->importer->import(self::FILE_PATH, self::SUPPLIER);
    }

    public function testRejectsMissingFile(): void
    {
        $this->expectException(StockImportInputException::class);
        $this->importer->import(__DIR__ . '/missing-legacy-import.csv', self::SUPPLIER);
    }

    private function expectPersistenceCalls(int $count): void
    {
        $this->stockItemRepository
            ->expects(self::exactly($count))
            ->method('findOneBy')
            ->willReturn(null);
        $this->entityManager
            ->expects(self::exactly($count))
            ->method('persist')
            ->with(self::isInstanceOf(StockItem::class));
    }

    private function createDto(string $externalId): StockItemImportDto
    {
        return new StockItemImportDto(
            externalId: $externalId,
            ean: '5901234567890',
            mpn: 'MPN-' . $externalId,
            producerName: 'Test Producer',
            price: '10.50',
            quantity: 5,
        );
    }
}
