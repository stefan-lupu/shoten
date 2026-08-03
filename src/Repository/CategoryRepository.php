<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Categorii de nivel superior (fără părinte) — folosit pentru meniul
     * de navigare. Subcategoriile (la orice adâncime) se încarcă lazy
     * pe măsură ce sunt afișate — ierarhia poate avea oricâte niveluri,
     * deci nu se pretează la eager-load cu un singur join.
     *
     * @return Category[]
     */
    public function findRootCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.orderNo', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * ID-urile categoriei date plus toți descendenții ei — folosit ca
     * o pagină de categorie-părinte să afișeze produsele din toate
     * subcategoriile, nu doar cele asociate direct.
     *
     * @return int[]
     */
    public function getSelfAndDescendantIds(Category $category): array
    {
        $ids = [$category->getId()];
        foreach ($category->getChildren() as $child) {
            $ids = array_merge($ids, $this->getSelfAndDescendantIds($child));
        }

        return $ids;
    }
}
