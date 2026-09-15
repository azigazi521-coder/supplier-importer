<?php

declare(strict_types=1);

namespace App\Tests\Service\Stock\Parser;

use App\Service\Stock\Parser\StockValueNormalizer;
use PHPUnit\Framework\TestCase;

final class StockValueNormalizerTest extends TestCase
{
    private StockValueNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new StockValueNormalizer();
    }

    public function testCleansWhitespaceAndQuotes(): void
    {
        $this->assertSame('value', $this->normalizer->clean(' "value" '));
    }

    public function testNormalizesPriceWithComma(): void
    {
        $this->assertSame('37.69', $this->normalizer->normalizePrice(' 37,69 '));
    }

    public function testNormalizesThresholdQuantityToCap(): void
    {
        $this->assertSame(31, $this->normalizer->normalizeQuantity('>30', '>30', 31));
        $this->assertSame(7, $this->normalizer->normalizeQuantity('7', '>30', 31));
    }

    public function testNormalizesEmptyEanToNull(): void
    {
        $this->assertNull($this->normalizer->normalizeEan(' "" '));
        $this->assertSame('5901234567890', $this->normalizer->normalizeEan(' 5901234567890 '));
    }

    public function testDetectsEmptyRows(): void
    {
        $this->assertTrue($this->normalizer->isEmptyRow([' ', '""', null]));
        $this->assertFalse($this->normalizer->isEmptyRow(['', 'value', '']));
    }
}
