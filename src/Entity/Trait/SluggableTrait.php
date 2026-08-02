<?php

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Generează automat slug-ul din `name` la creare (dacă nu e deja setat manual).
 * Clasa care folosește acest trait trebuie să aibă #[ORM\HasLifecycleCallbacks]
 * și o metodă getName(): ?string.
 */
trait SluggableTrait
{
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): void
    {
        if (!$this->slug && $this->getName()) {
            $this->slug = strtolower((new AsciiSlugger())->slug($this->getName())->toString());
        }
    }
}
