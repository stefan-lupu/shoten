<?php

namespace App\Entity;

use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NewsletterSubscriberRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Acest email este deja abonat.')]
class NewsletterSubscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Introdu adresa de email.')]
    #[Assert\Email(message: 'Adresa de email nu este validă.')]
    private ?string $email = null;

    #[ORM\Column]
    #[Assert\IsTrue(message: 'Trebuie să fii de acord cu prelucrarea datelor pentru a te abona.')]
    private bool $consentGiven = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $subscribedAt = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $unsubscribeToken = null;

    public function __construct()
    {
        $this->subscribedAt = new \DateTimeImmutable();
        $this->unsubscribeToken = bin2hex(random_bytes(16));
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isConsentGiven(): bool
    {
        return $this->consentGiven;
    }

    public function setConsentGiven(bool $consentGiven): static
    {
        $this->consentGiven = $consentGiven;

        return $this;
    }

    public function getSubscribedAt(): ?\DateTimeImmutable
    {
        return $this->subscribedAt;
    }

    public function getUnsubscribeToken(): ?string
    {
        return $this->unsubscribeToken;
    }
}
