<?php

namespace App\Entity;

use App\Enum\CampaignProductRole;
use App\Enum\CampaignType;
use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignRepository::class)]
class Campaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(enumType: CampaignType::class)]
    private ?CampaignType $type = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(length: 50, nullable: true, unique: true)]
    private ?string $couponCode = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $discountValue = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxUses = null;

    #[ORM\Column]
    private int $usesCount = 0;

    /**
     * @var Collection<int, CampaignProduct>
     */
    #[ORM\OneToMany(targetEntity: CampaignProduct::class, mappedBy: 'campaign', orphanRemoval: true, cascade: ['persist'])]
    private Collection $campaignProducts;

    public function __construct()
    {
        $this->campaignProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?CampaignType
    {
        return $this->type;
    }

    public function setType(CampaignType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }

    public function setCouponCode(?string $couponCode): static
    {
        $this->couponCode = $couponCode;

        return $this;
    }

    public function getDiscountValue(): ?string
    {
        return $this->discountValue;
    }

    public function setDiscountValue(?string $discountValue): static
    {
        $this->discountValue = $discountValue;

        return $this;
    }

    public function getMaxUses(): ?int
    {
        return $this->maxUses;
    }

    public function setMaxUses(?int $maxUses): static
    {
        $this->maxUses = $maxUses;

        return $this;
    }

    public function getUsesCount(): int
    {
        return $this->usesCount;
    }

    public function incrementUsesCount(): void
    {
        ++$this->usesCount;
    }

    /**
     * @return Collection<int, CampaignProduct>
     */
    public function getCampaignProducts(): Collection
    {
        return $this->campaignProducts;
    }

    /**
     * @return Product[]
     */
    public function getProductsByRole(CampaignProductRole $role): array
    {
        return array_values(array_map(
            static fn (CampaignProduct $cp) => $cp->getProduct(),
            array_filter(
                $this->campaignProducts->toArray(),
                static fn (CampaignProduct $cp) => $cp->getRole() === $role,
            ),
        ));
    }

    public function isWithinDateWindow(\DateTimeImmutable $now): bool
    {
        if ($this->startsAt && $now < $this->startsAt) {
            return false;
        }

        if ($this->endsAt && $now > $this->endsAt) {
            return false;
        }

        return true;
    }

    public function hasUsesRemaining(): bool
    {
        return null === $this->maxUses || $this->usesCount < $this->maxUses;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
