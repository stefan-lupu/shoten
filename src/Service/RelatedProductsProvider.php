<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;

/**
 * Construiește lista „Produse recomandate" pentru pagina unui produs, în ordinea:
 *
 *   1. produsele sugerate manual din admin (Product::getSuggestedProducts),
 *      în ordinea în care au fost adăugate;
 *   2. restul produselor în stoc din aceeași categorie;
 *   3. dacă aceeași categorie n-are niciun produs în stoc, produse aleatorii
 *      în stoc din tot magazinul.
 *
 * Produsul curent și duplicatele sunt excluse pe tot parcursul.
 */
class RelatedProductsProvider
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {
    }

    /**
     * @return Product[]
     */
    public function forProduct(Product $product, int $limit = 4): array
    {
        /** @var array<int, Product> $result indexat pe id, ca să evităm duplicatele */
        $result = [];
        $seen = [$product->getId() => true];

        // 1. Sugerate manual (pot fi și fără stoc — sunt alegerea explicită a adminului).
        foreach ($product->getSuggestedProducts() as $suggested) {
            if (isset($seen[$suggested->getId()])) {
                continue;
            }
            $result[$suggested->getId()] = $suggested;
            $seen[$suggested->getId()] = true;
            if (\count($result) >= $limit) {
                return array_values($result);
            }
        }

        // 2. Restul din aceeași categorie, în stoc.
        $categoryId = $product->getCategory()?->getId();
        $sameCategory = null !== $categoryId
            ? $this->products->findInStockByCategory($categoryId, array_keys($seen), $limit - \count($result))
            : [];
        foreach ($sameCategory as $candidate) {
            $result[$candidate->getId()] = $candidate;
            $seen[$candidate->getId()] = true;
        }

        // 3. Fallback: dacă aceeași categorie n-are nimic în stoc, produse aleatorii.
        if (\count($result) < $limit && [] === $sameCategory) {
            foreach ($this->products->findRandomInStock(array_keys($seen), $limit - \count($result)) as $candidate) {
                $result[$candidate->getId()] = $candidate;
            }
        }

        return array_values($result);
    }
}
