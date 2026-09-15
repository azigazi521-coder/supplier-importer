<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock\Transformer;

use App\Service\Stock\Parser\LorotomParser;
use App\Service\Stock\Parser\StockValueNormalizer;
use App\Service\Stock\Exception\StockImportInputException;
use PHPUnit\Framework\TestCase;

class LorotomTransformerTest extends TestCase
{
    private LorotomParser $parser;
    private string $tempFilePath;

    protected function setUp(): void
    {
        $this->parser = new LorotomParser(new StockValueNormalizer());
        $this->tempFilePath = sys_get_temp_dir() . '/transformer_lorotom_' . uniqid() . '.csv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    public function testTabSeparatedRecordTransformsToDto(): void
    {
        $headers = "price\tean\tproducer\tour_code\tquantity\tname\tproducer_code";
        $row = "37,69\t5907659302477\tSTATIM\t0AUE001\t>30\tAUTOMAT ROZRUSZ\tE001";

        file_put_contents($this->tempFilePath, implode("\n", [$headers, $row]));

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));
        $this->assertCount(1, $parsedData);

        $dto = $parsedData[0];

        $this->assertSame('0AUE001', $dto->externalId);
        $this->assertSame('E001', $dto->mpn);
        $this->assertSame('STATIM', $dto->producerName);
        $this->assertSame('5907659302477', $dto->ean);
        $this->assertSame('37.69', $dto->price);
        $this->assertSame(31, $dto->quantity);
    }

    public function testEmptyEanIsNormalizedToNull(): void
    {
        file_put_contents($this->tempFilePath, implode("\n", [
            "our_code\tproducer_code\tproducer\tquantity\tprice\tean",
            "0AUE002\tE002\tSTATIM\t5\t10,00\t",
        ]));

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));

        $this->assertNull($parsedData[0]->ean);
    }

    public function testMissingRequiredColumnThrowsInputException(): void
    {
        file_put_contents($this->tempFilePath, "our_code\tproducer_code\tproducer\tquantity\tprice\n");

        $this->expectException(StockImportInputException::class);
        iterator_to_array($this->parser->parse($this->tempFilePath));
    }

    public function testEmptyAndIncompleteRowsAreSkipped(): void
    {
        file_put_contents($this->tempFilePath, implode("\n", [
            "our_code\tproducer_code\tproducer\tquantity\tprice\tean",
            "\t\t\t\t\t",
            "incomplete\trow",
            "0AUE003\tE003\tSTATIM\t2\t5,00\t5900000000000",
        ]));

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));

        $this->assertCount(1, $parsedData);
        $this->assertSame('0AUE003', $parsedData[0]->externalId);
    }
}
