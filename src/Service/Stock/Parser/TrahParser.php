<?php

declare(strict_types=1);

namespace App\Service\Stock\Parser;

use App\Dto\StockItemImportDto;

class TrahParser implements SupplierStockParserInterface
{

    private const QUANTITY_CAP = 11;
    private const QUANTITY_THRESHOLD = '>10';
    private const SKIP_PRODUCERS = ['NARZEDZIA WARSZTAT'];

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
            while (($data = fgetcsv($handle, 0, ';', '"', "\\")) !== false) {

                if (count($data) < 6) {
                    continue;
                }

                $producerName = trim((string) ($data[5] ?? ''), " \t\n\r\0\x0B\"");

                if (in_array($producerName, self::SKIP_PRODUCERS)) {
                    continue;
                }

                yield $this->transform($data);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param list<string|null> $row
     */
    public function transform(array $row): StockItemImportDto
    {
        $externalId = trim((string) ($row[0] ?? ''), " \t\n\r\0\x0B\"");
        $quantity = $this->normalizeQuantity(trim((string) ($row[1] ?? '0')));
        $price = $this->normalizePrice((string) ($row[2] ?? '0'));
        $mpn = trim((string) ($row[3] ?? ''), " \t\n\r\0\x0B\"");
        $ean = $this->normalizeEan(isset($row[4]) ? (string) $row[4] : null);
        $producerName = trim((string) ($row[5] ?? ''), " \t\n\r\0\x0B\"");

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
        return ($quantity === self::QUANTITY_THRESHOLD) ? self::QUANTITY_CAP : (int) $quantity;
    }

    private function normalizePrice(string $price): string
    {
        $normalized = str_replace(',', '.', trim($price, " \t\n\r\0\x0B\""));

        return number_format((float) $normalized, 2, '.', '');
    }

    private function normalizeEan(?string $ean): ?string
    {
        if (null === $ean) {
            return null;
        }

        $ean = trim($ean, " \t\n\r\0\x0B\"");

        return '' === $ean ? null : $ean;
    }

    public function getSupplierName(): string
    {
        return 'trah';
    }
}
