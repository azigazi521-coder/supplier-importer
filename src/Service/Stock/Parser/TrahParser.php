<?php

declare(strict_types=1);

namespace App\Service\Stock\Parser;

use App\Dto\StockItemImportDto;
use App\Service\Stock\Exception\StockImportException;

class TrahParser implements SupplierStockParserInterface
{

    private const QUANTITY_CAP = 11;
    private const QUANTITY_THRESHOLD = '>10';
    private const SKIP_PRODUCERS = ['NARZEDZIA WARSZTAT'];

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
            while (($data = fgetcsv($handle, 0, ';', '"', "\\")) !== false) {

                if ($this->valueNormalizer->isEmptyRow($data) || count($data) < 6) {
                    continue;
                }

                $producerName = $this->valueNormalizer->clean($data[5] ?? null);

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
        $externalId = $this->valueNormalizer->clean($row[0] ?? null);
        $quantity = $this->valueNormalizer->normalizeQuantity(
            $row[1] ?? null,
            self::QUANTITY_THRESHOLD,
            self::QUANTITY_CAP,
        );
        $price = $this->valueNormalizer->normalizePrice($row[2] ?? null);
        $mpn = $this->valueNormalizer->clean($row[3] ?? null);
        $ean = $this->valueNormalizer->normalizeEan($row[4] ?? null);
        $producerName = $this->valueNormalizer->clean($row[5] ?? null);

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
        return 'trah';
    }
}
