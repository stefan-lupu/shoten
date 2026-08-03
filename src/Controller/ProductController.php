<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    private const int PER_PAGE = 12;

    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findFeatured(self::PER_PAGE),
        ]);
    }

    #[Route('/categorie/{slug}', name: 'app_category_show')]
    public function categoryShow(string $slug, Request $request, CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);
        if (!$category) {
            throw new NotFoundHttpException('Categoria nu a fost găsită.');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $categoryIds = $categoryRepository->getSelfAndDescendantIds($category);
        $products = $productRepository->paginateByCategoryIds($categoryIds, $page, self::PER_PAGE);
        $totalPages = (int) ceil(count($products) / self::PER_PAGE);

        return $this->render('product/category.html.twig', [
            'category' => $category,
            'products' => $products,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/cautare', name: 'app_product_search')]
    public function search(Request $request, ProductRepository $productRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $page = max(1, $request->query->getInt('page', 1));

        $products = [];
        $totalPages = 0;
        if ('' !== $query) {
            $products = $productRepository->paginateSearch($query, $page, self::PER_PAGE);
            $totalPages = (int) ceil(count($products) / self::PER_PAGE);
        }

        return $this->render('product/search.html.twig', [
            'query' => $query,
            'products' => $products,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/produs/{slug}', name: 'app_product_show')]
    public function show(string $slug, ProductRepository $productRepository, ReviewRepository $reviewRepository): Response
    {
        $product = $productRepository->findOneBy(['slug' => $slug]);
        if (!$product) {
            throw new NotFoundHttpException('Produsul nu a fost găsit.');
        }

        $user = $this->getUser();
        $reviewForm = null;
        $hasReviewed = false;

        if ($user) {
            $hasReviewed = null !== $reviewRepository->findOneByProductAndUser($product, $user);
            if (!$hasReviewed) {
                $reviewForm = $this->createForm(ReviewType::class, new Review());
            }
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'reviews' => $reviewRepository->findApprovedByProduct($product),
            'averageRating' => $reviewRepository->getAverageRating($product),
            'reviewForm' => $reviewForm?->createView(),
            'hasReviewed' => $hasReviewed,
        ]);
    }
}
