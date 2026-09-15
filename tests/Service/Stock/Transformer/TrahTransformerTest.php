<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock\Transformer;

use App\Service\Stock\Parser\TrahParser;
use App\Service\Stock\Parser\StockValueNormalizer;
use PHPUnit\Framework\TestCase;

class TrahTransformerTest extends TestCase
{
    private TrahParser $parser;
    private string $tempFilePath;

    protected function setUp(): void
    {
        $this->parser = new TrahParser(new StockValueNormalizer());
        $this->tempFilePath = sys_get_temp_dir() . '/transformer_trah_' . uniqid() . '.csv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
    }

    public function testSemicolonSeparatedRecordTransformsToDto(): void
    {
        $csvContent = '"000 014";>10;10,34;19-598;5905694015970;"AMTRA"';
        file_put_contents($this->tempFilePath, $csvContent);

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));
        $this->assertCount(1, $parsedData);

        $dto = $parsedData[0];

        $this->assertSame('000 014', $dto->externalId);
        $this->assertSame('5905694015970', $dto->ean);
        $this->assertSame('19-598', $dto->mpn);
        $this->assertSame('AMTRA', $dto->producerName);
        $this->assertSame('10.34', $dto->price);
        $this->assertSame(11, $dto->quantity);
    }

    public function testSkipsWorkshopToolsRecords(): void
    {
        $csvContent = '"000 999";5;15,00;99-999;1234567890123;"NARZEDZIA WARSZTAT"';
        file_put_contents($this->tempFilePath, $csvContent);

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));

        $this->assertEmpty($parsedData);
    }

    public function testEmptyEanIsNormalizedToNull(): void
    {
        file_put_contents($this->tempFilePath, '"000 016";3;12,00;19-600;;"AMTRA"');

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));

        $this->assertNull($parsedData[0]->ean);
    }

    public function testEmptyAndIncompleteRowsAreSkipped(): void
    {
        file_put_contents($this->tempFilePath, implode("\n", [
            ";;;;;",
            '"incomplete";5;10,00',
            '"000 015";3;12,00;19-599;5905694015971;"AMTRA"',
        ]));

        $parsedData = iterator_to_array($this->parser->parse($this->tempFilePath));

        $this->assertCount(1, $parsedData);
        $this->assertSame('000 015', $parsedData[0]->externalId);
    }
}
