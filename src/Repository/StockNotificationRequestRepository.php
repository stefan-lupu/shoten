<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\StockNotificationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockNotificationRequest>
 */
class StockNotificationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockNotificationRequest::class);
    }

    /**
     * @return StockNotificationRequest[]
     */
    public function findPendingForProduct(Product $product): array
    {
        return $this->findBy(['product' => $product, 'notifiedAt' => null]);
    }
}
