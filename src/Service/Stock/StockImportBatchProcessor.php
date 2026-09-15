<?php

declare(strict_types=1);

namespace App\Service\Stock;

use Doctrine\ORM\EntityManagerInterface;

final class StockImportBatchProcessor
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function flushIfNeeded(int $processedCount): void
    {
        if (($processedCount % self::BATCH_SIZE) === 0) {
            $this->flushAndClear();
        }
    }

    public function finish(): void
    {
        $this->flushAndClear();
    }

    private function flushAndClear(): void
    {
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
