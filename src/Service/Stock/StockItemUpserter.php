<?php

declare(strict_types=1);

namespace App\Service\Stock;

use App\Dto\StockItemImportDto;
use App\Entity\StockItem;
use App\Repository\StockItemRepository;
use Doctrine\ORM\EntityManagerInterface;

final class StockItemUpserter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StockItemRepository $stockItemRepository,
    ) {}

    public function upsert(string $supplierName, StockItemImportDto $dto): void
    {
        $stockItem = $this->stockItemRepository->findOneBy([
            'supplier' => $supplierName,
            'externalId' => $dto->externalId,
        ]);

        if (!$stockItem) {
            $stockItem = new StockItem();
            $stockItem->setSupplier($supplierName);
            $stockItem->setExternalId($dto->externalId);
            $this->entityManager->persist($stockItem);
        }

        $stockItem->setEan($dto->ean);
        $stockItem->setMpn($dto->mpn);
        $stockItem->setProducerName($dto->producerName);
        $stockItem->setPrice($dto->price);
        $stockItem->setQuantity($dto->quantity);
    }
}
