<?php

namespace App\Repository;

use App\Entity\ShippingSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShippingSettings>
 */
class ShippingSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingSettings::class);
    }

    /**
     * Setările sunt un singur rând (creat de migrare) — dacă lipsește
     * dintr-un motiv oarecare, se creează unul cu valorile implicite.
     */
    public function getSettings(): ShippingSettings
    {
        $settings = $this->findOneBy([]);
        if (!$settings) {
            $settings = new ShippingSettings();
            $entityManager = $this->getEntityManager();
            $entityManager->persist($settings);
            $entityManager->flush();
        }

        return $settings;
    }
}
