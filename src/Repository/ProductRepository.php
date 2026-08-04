<?php

namespace App\Repository;

use App\Entity\Product;
use App\Enum\StockStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * Chei valide pentru parametrul `sort` din URL (căutare/categorie).
     */
    private const array SORT_OPTIONS = [
        'price_asc' => ['p.price', 'ASC'],
        'price_desc' => ['p.price', 'DESC'],
        'newest' => ['p.createdAt', 'DESC'],
        'name' => ['p.name', 'ASC'],
    ];

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
    public function paginateByCategoryIds(array $categoryIds, int $page, int $perPage = 12, ?string $sort = null, ?string $minPrice = null, ?string $maxPrice = null): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.category IN (:categoryIds)')
            ->setParameter('categoryIds', $categoryIds)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
        ;
        $this->applyPriceRange($qb, $minPrice, $maxPrice);
        $this->applySort($qb, $sort);

        return new Paginator($qb->getQuery());
    }

    public function paginateSearch(string $query, int $page, int $perPage = 12, ?string $sort = null, ?string $minPrice = null, ?string $maxPrice = null): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :query OR p.description LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
        ;
        $this->applyPriceRange($qb, $minPrice, $maxPrice);
        $this->applySort($qb, $sort);

        return new Paginator($qb->getQuery());
    }

    private function applySort(QueryBuilder $qb, ?string $sort): void
    {
        [$field, $direction] = self::SORT_OPTIONS[$sort] ?? self::SORT_OPTIONS['name'];
        $qb->orderBy($field, $direction);
    }

    private function applyPriceRange(QueryBuilder $qb, ?string $minPrice, ?string $maxPrice): void
    {
        if (null !== $minPrice && is_numeric($minPrice)) {
            $qb->andWhere('p.price >= :minPrice')->setParameter('minPrice', $minPrice);
        }
        if (null !== $maxPrice && is_numeric($maxPrice)) {
            $qb->andWhere('p.price <= :maxPrice')->setParameter('maxPrice', $maxPrice);
        }
    }

    /**
     * Produse in_stock cu stoc sub pragul dat — pentru alerta din dashboard-ul admin.
     *
     * @return Product[]
     */
    public function findLowStock(int $threshold): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stockStatus = :status')
            ->andWhere('p.stock < :threshold')
            ->setParameter('status', StockStatus::InStock)
            ->setParameter('threshold', $threshold)
            ->orderBy('p.stock', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
