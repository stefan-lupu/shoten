<?php

namespace App\Repository;

use App\Entity\Campaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campaign>
 */
class CampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    /**
     * Campanii active, indiferent de fereastra de date — filtrarea pe
     * startsAt/endsAt se face în PHP (Campaign::isWithinDateWindow) ca
     * să nu depindem de fusul orar al bazei de date.
     *
     * @return Campaign[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = true')
            ->getQuery()
            ->getResult()
        ;
    }
}
