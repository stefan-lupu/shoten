<?php

namespace App\Entity;

use App\Repository\ProductWholesaleTierRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductWholesaleTierRepository::class)]
class ProductWholesaleTier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'wholesaleTiers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    /**
     * Pragul de cantitate de la care se aplică `unitPrice` — vezi
     * App\Service\WholesalePricingResolver pentru logica de selecție a
     * tier-ului aplicabil.
     */
    #[ORM\Column]
    private int $minQuantity = 1;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $unitPrice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getMinQuantity(): int
    {
        return $this->minQuantity;
    }

    public function setMinQuantity(int $minQuantity): static
    {
        $this->minQuantity = $minQuantity;

        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }
}
