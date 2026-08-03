<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findByCategorySlug(string $slug): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Cele mai recent adăugate produse — folosite ca „featured” pe pagina principală.
     *
     * @return Product[]
     */
    public function findFeatured(int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Paginează produsele dintr-una sau mai multe categorii deodată —
     * pentru o categorie-părinte, se dă id-ul ei plus id-urile
     * subcategoriilor (App\Repository\CategoryRepository::getSelfAndDescendantIds),
     * ca produsele din subcategorii să apară și pe pagina părintelui.
     *
     * @param int[] $categoryIds
     */
    public function paginateByCategoryIds(array $categoryIds, int $page, int $perPage = 12): Paginator
    {
        $query = $this->createQueryBuilder('p')
            ->andWhere('p.category IN (:categoryIds)')
            ->setParameter('categoryIds', $categoryIds)
            ->orderBy('p.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
        ;

        return new Paginator($query);
    }

    public function paginateSearch(string $query, int $page, int $perPage = 12): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :query OR p.description LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('p.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
        ;

        return new Paginator($qb);
    }
}
