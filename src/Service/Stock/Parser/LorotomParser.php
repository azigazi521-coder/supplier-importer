<?php

declare(strict_types=1);

namespace App\Service\Stock\Parser;

use App\Dto\StockItemImportDto;
use App\Service\Stock\Exception\StockImportException;
use App\Service\Stock\Exception\StockImportInputException;

class LorotomParser implements SupplierStockParserInterface
{

    private const QUANTITY_CAP = 31;
    private const QUANTITY_THRESHOLD = '>30';
    private const array REQUIRED_COLUMNS = [
        'our_code',
        'producer_code',
        'producer',
        'quantity',
        'price',
        'ean',
    ];

    public function __construct(
        private readonly StockValueNormalizer $valueNormalizer,
    ) {}

    public function parse(string $filePath): iterable
    {
        $handle = fopen($filePath, 'rb');
        if (false === $handle) {
            throw new StockImportException(sprintf('Unable to open file: %s', $filePath));
        }

        try {
            $headers = fgetcsv($handle, 0, "\t", '"', "\\");
            if ($headers === false) {
                fclose($handle);
                return;
            }

            $headerMap = array_flip(array_map($this->valueNormalizer->clean(...), $headers));

            foreach (self::REQUIRED_COLUMNS as $col) {
                if (!isset($headerMap[$col])) {
                    fclose($handle);
                    throw new StockImportInputException("Column '{$col}' is required in file '{$filePath}'.");
                }
            }

            while (($row = fgetcsv($handle, 0, "\t", '"', '\\')) !== false) {
                if ($this->valueNormalizer->isEmptyRow($row) || count($row) < count($headers)) {
                    continue;
                }

                yield $this->transform($row, $headerMap);
            }
        } finally {
            fclose($handle);
        }
    }


    /**
     * @param list<string|null> $row
     */
    public function transform(array $row, array $headerMap): StockItemImportDto
    {

        $externalId = $this->valueNormalizer->clean($row[$headerMap['our_code']] ?? null);
        $mpn = $this->valueNormalizer->clean($row[$headerMap['producer_code']] ?? null);
        $producerName = $this->valueNormalizer->clean($row[$headerMap['producer']] ?? null);
        $quantity = $this->valueNormalizer->normalizeQuantity(
            $row[$headerMap['quantity']] ?? null,
            self::QUANTITY_THRESHOLD,
            self::QUANTITY_CAP,
        );
        $price = $this->valueNormalizer->normalizePrice($row[$headerMap['price']] ?? null);
        $ean = $this->valueNormalizer->normalizeEan($row[$headerMap['ean']] ?? null);

        return new StockItemImportDto(
            ean: $ean,
            mpn: $mpn,
            producerName: $producerName,
            externalId: $externalId,
            price: $price,
            quantity: $quantity,
        );
    }

    public function getSupplierName(): string
    {
        return 'lorotom';
    }
}
