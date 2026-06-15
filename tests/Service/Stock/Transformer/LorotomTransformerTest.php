<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock\Transformer;

use App\Entity\StockItem;
use App\Service\Stock\Parser\LorotomParser;
use PHPUnit\Framework\TestCase;

class LorotomTransformerTest extends TestCase
{
    private LorotomParser $parser;
    private string $tempFilePath;

    protected function setUp(): void
    {
        $this->parser = new LorotomParser();
        $this->tempFilePath = sys_get_temp_dir() . '/transformer_lorotom_' . uniqid() . '.csv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    public function testTabSeparatedRecordTransformsToStockItemEntity(): void
    {
        $headers = "our_code\tproducer_code\tname\tproducer\tquantity\tprice\tean";
        $row = "0AUE001\tE001\tAUTOMAT ROZRUSZ\tSTATIM\t>30\t37,69\t5907659302477";
        
        file_put_contents($this->tempFilePath, implode("\n", [$headers, $row]));

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));
        $this->assertCount(1, $parsedData);
        
        $dto = array_shift($parsedData);

        $stockItem = new StockItem();
        $stockItem->setSupplier('lorotom');
        $stockItem->setExternalId($dto->externalId);
        $stockItem->setEan($dto->ean);
        $stockItem->setMpn($dto->mpn);
        $stockItem->setProducerName($dto->producerName);
        $stockItem->setPrice($dto->price);
        $stockItem->setQuantity($dto->quantity);

        $this->assertInstanceOf(StockItem::class, $stockItem);
        $this->assertSame('lorotom', $stockItem->getSupplier());
        $this->assertSame('0AUE001', $stockItem->getExternalId());
        $this->assertSame('E001', $stockItem->getMpn());
        $this->assertSame('STATIM', $stockItem->getProducerName());
        $this->assertSame('5907659302477', $stockItem->getEan());
        $this->assertSame(37.69, (float) $stockItem->getPrice());
        $this->assertSame(31, $stockItem->getQuantity());
    }
}
