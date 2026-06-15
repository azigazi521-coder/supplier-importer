<?php

declare(strict_types=1);

namespace App\Service\Stock\Parser;

use App\Dto\StockItemImportDto;

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

    public function parse(string $filePath): iterable
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException("File not found or unreadable: $filePath");
        }

        $handle = fopen($filePath, 'rb');
        if (false === $handle) {
            throw new \RuntimeException(sprintf('Unable to open file: %s', $filePath));
        }

        try {
            $headers = fgetcsv($handle, 0, "\t", '"', "\\");
            if ($headers === false) {
                fclose($handle);
                return;
            }

            $headerMap = array_flip(array_map('trim', $headers));

            foreach (self::REQUIRED_COLUMNS as $col) {
                if (!isset($headerMap[$col])) {
                    fclose($handle);
                    throw new \RuntimeException("Column '{$col}' is required in file '{$filePath}'.");
                }
            }

            while (($row = fgetcsv($handle, 0, "\t", '"', '\\')) !== false) {
                if (count($row) < count($headers)) {
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

        $externalId = trim((string) ($row[$headerMap['our_code']] ?? ''));
        $mpn = trim((string) ($row[$headerMap['producer_code']] ?? ''));
        $producerName = trim((string) ($row[$headerMap['producer']] ?? ''));
        $quantity = $this->normalizeQuantity((string) trim((string) ($row[$headerMap['quantity']] ?? '0')));
        $price = $this->normalizePrice((string) ($row[$headerMap['price']] ?? '0'));
        $ean = $this->normalizeEan(isset($row[$headerMap['ean']]) ? (string) $row[$headerMap['ean']] : null);

        return new StockItemImportDto(
            ean: $ean,
            mpn: $mpn,
            producerName: $producerName,
            externalId: $externalId,
            price: $price,
            quantity: $quantity,
        );
    }

    private function normalizeQuantity(string $quantity): int
    {

        if ($quantity === self::QUANTITY_THRESHOLD) {
            return self::QUANTITY_CAP;
        }

        return (int) $quantity;
    }

    private function normalizePrice(string $price): string
    {
        $normalized = str_replace(',', '.', trim($price));

        return number_format((float) $normalized, 2, '.', '');
    }

    private function normalizeEan(?string $ean): ?string
    {
        if (null === $ean) {
            return null;
        }

        $ean = trim($ean);

        return '' === $ean ? null : $ean;
    }

    public function getSupplierName(): string
    {
        return 'lorotom';
    }
}
