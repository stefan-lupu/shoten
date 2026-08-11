<?php

namespace App\Entity;

use App\Repository\InvoiceSequenceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contor secvențial per serie de factură. Există câte un rând per serie
 * (ex: „RJ") — vezi App\Service\InvoiceNumberAllocator, care blochează
 * rândul (SELECT ... FOR UPDATE) la alocarea următorului număr, garantând
 * numere unice și fără goluri chiar și la cereri concurente.
 */
#[ORM\Entity(repositoryClass: InvoiceSequenceRepository::class)]
class InvoiceSequence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $series = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $lastNumber = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeries(): ?string
    {
        return $this->series;
    }

    public function setSeries(string $series): static
    {
        $this->series = $series;

        return $this;
    }

    public function getLastNumber(): int
    {
        return $this->lastNumber;
    }

    public function setLastNumber(int $lastNumber): static
    {
        $this->lastNumber = $lastNumber;

        return $this;
    }
}
