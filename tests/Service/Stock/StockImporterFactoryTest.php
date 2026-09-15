<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock;

use App\Service\Stock\LegacyStockImporter;
use App\Service\Stock\MultilineStockImporter;
use App\Service\Stock\StockImportMode;
use App\Service\Stock\StockImporterFactory;
use App\Service\Stock\StockImporterInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StockImporterFactoryTest extends KernelTestCase
{
    private StockImporterFactory $factory;

    protected function setUp(): void
    {
        self::bootKernel();

        $factory = static::getContainer()->get(StockImporterFactory::class);
        self::assertInstanceOf(StockImporterFactory::class, $factory);
        $this->factory = $factory;
    }

    public function testLegacyModeReturnsLegacyImporter(): void
    {
        $importer = $this->factory->create(StockImportMode::LEGACY);

        self::assertInstanceOf(LegacyStockImporter::class, $importer);
        self::assertInstanceOf(StockImporterInterface::class, $importer);
    }

    public function testMultilineModeReturnsMultilineImporter(): void
    {
        $importer = $this->factory->create(StockImportMode::MULTILINE);

        self::assertInstanceOf(MultilineStockImporter::class, $importer);
        self::assertInstanceOf(StockImporterInterface::class, $importer);
    }
}
