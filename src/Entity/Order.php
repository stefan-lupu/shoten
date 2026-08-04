<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Adresa de livrare e copiată (snapshot) la plasarea comenzii, ca să nu
    // se schimbe retroactiv dacă userul își editează adresa salvată ulterior.
    #[ORM\Column(length: 255)]
    private ?string $shippingFullName = null;

    #[ORM\Column(length: 30)]
    private ?string $shippingPhone = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingCounty = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingCity = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingStreet = null;

    #[ORM\Column(length: 20)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Pending;

    #[ORM\Column(enumType: PaymentMethod::class)]
    private ?PaymentMethod $paymentMethod = null;

    #[ORM\Column(enumType: PaymentStatus::class)]
    private PaymentStatus $paymentStatus = PaymentStatus::Pending;

    /**
     * Referința sesiunii de plată la providerul de card (folosită pentru
     * a lega webhook-ul de confirmare de comanda corectă).
     */
    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $total = null;

    /**
     * Cost transport la momentul comenzii — deja inclus în `total`, păstrat
     * separat doar pentru afișare (linie distinctă pe comandă/factură).
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $shippingCost = '0.00';

    /**
     * Codul de cupon folosit la plasarea comenzii, dacă a fost cazul —
     * doar pentru trasabilitate/afișare, nu recalculează nimic.
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $couponCode = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Setat prima dată când se trimite evenimentul de conversie către
     * Google Ads pentru această comandă — garantează că se trimite o
     * singură dată, indiferent de câte ori e revizitată pagina comenzii.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $adsConversionSentAt = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', orphanRemoval: true, cascade: ['persist'])]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getShippingFullName(): ?string
    {
        return $this->shippingFullName;
    }

    public function setShippingFullName(string $shippingFullName): static
    {
        $this->shippingFullName = $shippingFullName;

        return $this;
    }

    public function getShippingPhone(): ?string
    {
        return $this->shippingPhone;
    }

    public function setShippingPhone(string $shippingPhone): static
    {
        $this->shippingPhone = $shippingPhone;

        return $this;
    }

    public function getShippingCounty(): ?string
    {
        return $this->shippingCounty;
    }

    public function setShippingCounty(string $shippingCounty): static
    {
        $this->shippingCounty = $shippingCounty;

        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(string $shippingCity): static
    {
        $this->shippingCity = $shippingCity;

        return $this;
    }

    public function getShippingStreet(): ?string
    {
        return $this->shippingStreet;
    }

    public function setShippingStreet(string $shippingStreet): static
    {
        $this->shippingStreet = $shippingStreet;

        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function setShippingPostalCode(string $shippingPostalCode): static
    {
        $this->shippingPostalCode = $shippingPostalCode;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(PaymentStatus $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): static
    {
        $this->paymentReference = $paymentReference;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getShippingCost(): string
    {
        return $this->shippingCost;
    }

    public function setShippingCost(string $shippingCost): static
    {
        $this->shippingCost = $shippingCost;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAdsConversionSentAt(): ?\DateTimeImmutable
    {
        return $this->adsConversionSentAt;
    }

    public function markAdsConversionSent(): static
    {
        $this->adsConversionSentAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }
}
