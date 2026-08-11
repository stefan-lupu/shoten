<?php

namespace App\Entity;

use App\Repository\ShippingSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Setări de transport — un singur rând, editabil din admin (nu din
 * .env.local, spre deosebire de restul StoreConfig), pentru că prețul de
 * transport se schimbă operațional, nu la clonarea magazinului.
 */
#[ORM\Entity(repositoryClass: ShippingSettingsRepository::class)]
class ShippingSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $cost = '15.00';

    /**
     * Prag de la care transportul devine gratuit. Null = niciodată gratuit.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $freeShippingThreshold = '200.00';

    /**
     * Valoarea minimă (lei) a unei comenzi plasate de un cont angro. Null =
     * fără prag de valoare. Se aplică doar comenzilor angro, nu retail.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $wholesaleMinOrderValue = null;

    /**
     * Numărul minim total de bucăți dintr-o comandă angro. Null = fără prag
     * de cantitate. Se aplică doar comenzilor angro, nu retail.
     */
    #[ORM\Column(nullable: true)]
    private ?int $wholesaleMinOrderItems = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCost(): string
    {
        return $this->cost;
    }

    public function setCost(string $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getFreeShippingThreshold(): ?string
    {
        return $this->freeShippingThreshold;
    }

    public function setFreeShippingThreshold(?string $freeShippingThreshold): static
    {
        $this->freeShippingThreshold = $freeShippingThreshold;

        return $this;
    }

    public function getWholesaleMinOrderValue(): ?string
    {
        return $this->wholesaleMinOrderValue;
    }

    public function setWholesaleMinOrderValue(?string $wholesaleMinOrderValue): static
    {
        $this->wholesaleMinOrderValue = $wholesaleMinOrderValue;

        return $this;
    }

    public function getWholesaleMinOrderItems(): ?int
    {
        return $this->wholesaleMinOrderItems;
    }

    public function setWholesaleMinOrderItems(?int $wholesaleMinOrderItems): static
    {
        $this->wholesaleMinOrderItems = $wholesaleMinOrderItems;

        return $this;
    }
}
