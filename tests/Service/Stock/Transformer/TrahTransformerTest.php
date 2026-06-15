<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock\Transformer;

use App\Entity\StockItem;
use App\Service\Stock\Parser\TrahParser;
use PHPUnit\Framework\TestCase;

class TrahTransformerTest extends TestCase
{
    private TrahParser $parser;
    private string $tempFilePath;

    protected function setUp(): void
    {
        $this->parser = new TrahParser();
        $this->tempFilePath = sys_get_temp_dir() . '/transformer_trah_' . uniqid() . '.csv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    public function testCsvRecordTransformsToStockItemEntity(): void
    {
        $csvContent = '"000 014";>10;10,34;19-598;5905694015970;"AMTRA"';
        file_put_contents($this->tempFilePath, $csvContent);

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));
        $this->assertCount(1, $parsedData);

        $dto = array_shift($parsedData);

        $stockItem = new StockItem();
        $stockItem->setSupplier('trah');
        $stockItem->setExternalId($dto->externalId);
        $stockItem->setEan($dto->ean);
        $stockItem->setMpn($dto->mpn);
        $stockItem->setProducerName($dto->producerName);
        $stockItem->setPrice($dto->price);
        $stockItem->setQuantity($dto->quantity);

        $this->assertInstanceOf(StockItem::class, $stockItem);
        $this->assertSame('trah', $stockItem->getSupplier());
        $this->assertSame('000 014', $stockItem->getExternalId());
        $this->assertSame('5905694015970', $stockItem->getEan());
        $this->assertSame('19-598', $stockItem->getMpn());
        $this->assertSame('AMTRA', $stockItem->getProducerName());
        $this->assertSame(10.34, (float) $stockItem->getPrice());
        $this->assertSame(11, $stockItem->getQuantity());
    }

    public function testTransformerSkipsWorkshopToolsRecords(): void
    {
        $csvContent = '"000 999";5;15,00;99-999;1234567890123;"NARZEDZIA WARSZTAT"';
        file_put_contents($this->tempFilePath, $csvContent);

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));

        $this->assertEmpty($parsedData);
    }
}
