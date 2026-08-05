<?php

namespace App\Repository;

use App\Entity\Cart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /**
     * Coșuri de clienți autentificați, cu produse, neatinse de cel puțin
     * $olderThan dar nu mai vechi de $newerThan (ca să nu retrimitem
     * remindere pentru coșuri abandonate de luni de zile) și cărora nu li
     * s-a trimis deja un reminder.
     *
     * @return Cart[]
     */
    public function findAbandoned(\DateTimeImmutable $olderThan, \DateTimeImmutable $newerThan): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user IS NOT NULL')
            ->andWhere('c.updatedAt < :olderThan')
            ->andWhere('c.updatedAt > :newerThan')
            ->andWhere('c.reminderSentAt IS NULL')
            ->andWhere('SIZE(c.items) > 0')
            ->setParameter('olderThan', $olderThan)
            ->setParameter('newerThan', $newerThan)
            ->getQuery()
            ->getResult();
    }
}
