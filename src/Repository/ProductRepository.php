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
            ->orderBy('p.isPromoted', 'DESC')
            ->addOrderBy('p.name', 'ASC')
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
        // Produsele promovate apar primele pe homepage, apoi cele mai recente.
        return $this->createQueryBuilder('p')
            ->orderBy('p.isPromoted', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
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
        // Produsele promovate au prioritate — apar primele, indiferent de
        // sortarea aleasă, care rămâne criteriul secundar.
        $qb->orderBy('p.isPromoted', 'DESC')->addOrderBy($field, $direction);
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
     * Produse aflate în stoc dintr-o categorie, excluzând id-urile date
     * (produsul curent + cele deja sugerate). Promovatele apar primele.
     * Folosit de App\Service\RelatedProductsProvider.
     *
     * @param int[] $excludeIds
     *
     * @return Product[]
     */
    public function findInStockByCategory(int $categoryId, array $excludeIds, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->andWhere('p.stockStatus = :status')
            ->andWhere('p.stock > 0')
            ->andWhere('p.id NOT IN (:exclude)')
            ->setParameter('category', $categoryId)
            ->setParameter('status', StockStatus::InStock)
            ->setParameter('exclude', $excludeIds ?: [0])
            ->orderBy('p.isPromoted', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Produse aflate în stoc din tot magazinul, în ordine aleatorie —
     * fallback pentru „Produse recomandate" când categoria produsului n-are
     * nimic în stoc. Amestecăm în PHP (DQL n-are RAND() portabil) pe un
     * pool mărginit, ca să nu încărcăm tot catalogul.
     *
     * @param int[] $excludeIds
     *
     * @return Product[]
     */
    public function findRandomInStock(array $excludeIds, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $pool = $this->createQueryBuilder('p')
            ->andWhere('p.stockStatus = :status')
            ->andWhere('p.stock > 0')
            ->andWhere('p.id NOT IN (:exclude)')
            ->setParameter('status', StockStatus::InStock)
            ->setParameter('exclude', $excludeIds ?: [0])
            ->setMaxResults(30)
            ->getQuery()
            ->getResult()
        ;
        shuffle($pool);

        return \array_slice($pool, 0, $limit);
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
