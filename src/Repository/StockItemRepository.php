<?php

namespace App\Repository;

use App\Entity\StockItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class StockItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockItem::class);
    }

    public function findByMpnOrEan(?string $mpn, ?string $ean): array
    {
        $qb = $this->createQueryBuilder('s');

        if ($mpn !== null && $ean !== null) {
            $qb->where('s.mpn = :mpn OR s.ean = :ean')
                ->setParameter('mpn', $mpn)
                ->setParameter('ean', $ean);
        } elseif ($mpn !== null) {
            $qb->where('s.mpn = :mpn')
                ->setParameter('mpn', $mpn);
        } elseif ($ean !== null) {
            $qb->where('s.ean = :ean')
                ->setParameter('ean', $ean);
        } else {
            return [];
        }

        

        return $qb->getQuery()->getResult();
    }
}
