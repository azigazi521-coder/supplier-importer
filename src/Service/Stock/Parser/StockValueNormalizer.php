<?php

declare(strict_types=1);

namespace App\Service\Stock\Parser;

final class StockValueNormalizer
{
    public function clean(?string $value): string
    {
        return trim((string) $value, " \t\n\r\0\x0B\"");
    }

    public function normalizePrice(?string $price): string
    {
        $normalized = str_replace(',', '.', $this->clean($price));

        return number_format((float) $normalized, 2, '.', '');
    }

    public function normalizeQuantity(?string $quantity, string $threshold, int $cap): int
    {
        $normalized = $this->clean($quantity);

        return $normalized === $threshold ? $cap : (int) $normalized;
    }

    public function normalizeEan(?string $ean): ?string
    {
        $normalized = $this->clean($ean);

        return '' === $normalized ? null : $normalized;
    }

    /**
     * @param list<string|null> $row
     */
    public function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ('' !== $this->clean($value)) {
                return false;
            }
        }

        return true;
    }
}
