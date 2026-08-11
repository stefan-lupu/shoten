<?php

namespace App\Entity;

use App\Enum\ReturnStatus;
use App\Repository\ReturnRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cerere de retur (drept de retragere 14 zile) pentru o comandă livrată.
 * Se creează de client din pagina comenzii și e procesată de admin. Un
 * order are cel mult o cerere de retur (impus în controller la creare).
 */
#[ORM\Entity(repositoryClass: ReturnRequestRepository::class)]
class ReturnRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(type: 'text')]
    private ?string $reason = null;

    #[ORM\Column(enumType: ReturnStatus::class)]
    private ReturnStatus $status = ReturnStatus::Requested;

    /**
     * Răspunsul/nota admin-ului la procesarea cererii — trimis clientului
     * pe email (ex: instrucțiuni de retur sau motivul respingerii).
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminNote = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getStatus(): ReturnStatus
    {
        return $this->status;
    }

    public function setStatus(ReturnStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): static
    {
        $this->adminNote = $adminNote;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function markProcessed(): static
    {
        $this->processedAt = new \DateTimeImmutable();

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Retur #%d', $this->id ?? 0);
    }
}
