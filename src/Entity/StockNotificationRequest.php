<?php

namespace App\Entity;

use App\Repository\StockNotificationRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cerere „anunță-mă când revine în stoc" pentru un produs epuizat
 * (StockStatus::InStock cu stock = 0). Nu necesită cont — doar un email.
 */
#[ORM\Entity(repositoryClass: StockNotificationRequestRepository::class)]
class StockNotificationRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $notifiedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function markNotified(): static
    {
        $this->notifiedAt = new \DateTimeImmutable();

        return $this;
    }
}
