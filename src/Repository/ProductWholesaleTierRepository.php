<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductWholesaleTier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductWholesaleTier>
 */
class ProductWholesaleTierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductWholesaleTier::class);
    }

    /**
     * Tier-ul cu cel mai mare `minQuantity` ≤ $quantity pentru produsul dat,
     * sau null dacă niciun prag nu e atins (cantitate sub primul prag).
     */
    public function findApplicableTier(Product $product, int $quantity): ?ProductWholesaleTier
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.product = :product')
            ->andWhere('t.minQuantity <= :quantity')
            ->setParameter('product', $product)
            ->setParameter('quantity', $quantity)
            ->orderBy('t.minQuantity', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
