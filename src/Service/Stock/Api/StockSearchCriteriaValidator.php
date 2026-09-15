<?php

declare(strict_types=1);

namespace App\Service\Stock\Api;

final class StockSearchCriteriaValidator
{
    public function validate(mixed $mpn, mixed $ean): StockSearchCriteria
    {
        $normalizedMpn = $this->normalize($mpn);
        $normalizedEan = $this->normalize($ean);

        if (null === $normalizedMpn && null === $normalizedEan) {
            throw new InvalidStockQueryException(
                'At least one query attribute (mpn or ean) must be specified.'
            );
        }

        return new StockSearchCriteria($normalizedMpn, $normalizedEan);
    }

    private function normalize(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }
}
