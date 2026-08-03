<?php

namespace App\Entity;

use App\Entity\Trait\SluggableTrait;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Există deja o categorie cu acest slug.')]
class Category
{
    use SluggableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Ordinea de afișare (ASC) printre categoriile de pe același nivel —
     * atât rădăcinile, cât și subcategoriile unui părinte se ordonează
     * după acest câmp, nu alfabetic.
     */
    #[ORM\Column(name: 'order_no')]
    private int $orderNo = 0;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category')]
    private Collection $products;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Category $parent = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['orderNo' => 'ASC'])]
    private Collection $children;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->children = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getOrderNo(): int
    {
        return $this->orderNo;
    }

    public function setOrderNo(int $orderNo): static
    {
        $this->orderNo = $orderNo;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function isRoot(): bool
    {
        return null === $this->parent;
    }

    /**
     * @return Category[] Lanțul de la categoria rădăcină până la aceasta (inclusiv), pentru breadcrumb.
     */
    public function getAncestryChain(): array
    {
        $chain = [$this];
        $current = $this;
        while ($current->getParent()) {
            $current = $current->getParent();
            array_unshift($chain, $current);
        }

        return $chain;
    }
}
