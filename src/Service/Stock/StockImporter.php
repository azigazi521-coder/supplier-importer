<?php

declare(strict_types=1);

namespace App\Service\Stock;

use App\Entity\StockItem;
use App\Repository\StockItemRepository;
use App\Service\Stock\Parser\StockParserFactory;
use Doctrine\ORM\EntityManagerInterface;


class StockImporter
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly StockParserFactory $parserFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly StockItemRepository $stockItemRepository
    ) {}

    public function import(string $filePath, string $supplierName): int
    {
        $parser = $this->parserFactory->getParser($supplierName);
        $stockDataIterator = $parser->parse($filePath);
        $processedCount = 0;

        foreach ($stockDataIterator as $dto) {
            
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

            $processedCount++;

            if (($processedCount % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear(); 
            }
        }

        
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $processedCount;
    }
}
