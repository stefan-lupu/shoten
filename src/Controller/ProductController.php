<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\StockNotificationRequest;
use App\Enum\StockStatus;
use App\Form\ReviewType;
use App\Form\StockNotificationRequestType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Service\StockNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        $sort = $request->query->get('sort');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $categoryIds = $categoryRepository->getSelfAndDescendantIds($category);
        $products = $productRepository->paginateByCategoryIds($categoryIds, $page, self::PER_PAGE, $sort, $minPrice, $maxPrice);
        $totalPages = (int) ceil(count($products) / self::PER_PAGE);

        return $this->render('product/category.html.twig', [
            'category' => $category,
            'products' => $products,
            'page' => $page,
            'totalPages' => $totalPages,
            'sort' => $sort,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }

    #[Route('/cautare', name: 'app_product_search')]
    public function search(Request $request, ProductRepository $productRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $page = max(1, $request->query->getInt('page', 1));
        $sort = $request->query->get('sort');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');

        $products = [];
        $totalPages = 0;
        if ('' !== $query) {
            $products = $productRepository->paginateSearch($query, $page, self::PER_PAGE, $sort, $minPrice, $maxPrice);
            $totalPages = (int) ceil(count($products) / self::PER_PAGE);
        }

        return $this->render('product/search.html.twig', [
            'query' => $query,
            'products' => $products,
            'page' => $page,
            'totalPages' => $totalPages,
            'sort' => $sort,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
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

        $stockNotificationForm = null;
        if (StockStatus::InStock === $product->getStockStatus() && $product->getStock() <= 0) {
            $stockNotificationForm = $this->createForm(
                StockNotificationRequestType::class,
                (new StockNotificationRequest())->setProduct($product),
            );
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'reviews' => $reviewRepository->findApprovedByProduct($product),
            'averageRating' => $reviewRepository->getAverageRating($product),
            'reviewForm' => $reviewForm?->createView(),
            'hasReviewed' => $hasReviewed,
            'stockNotificationForm' => $stockNotificationForm?->createView(),
        ]);
    }

    #[Route('/produs/{slug}/anunta-ma', name: 'app_product_notify_stock', methods: ['POST'])]
    public function notifyStock(string $slug, Request $request, ProductRepository $productRepository, StockNotificationService $stockNotificationService): RedirectResponse
    {
        $product = $productRepository->findOneBy(['slug' => $slug]);
        if (!$product) {
            throw new NotFoundHttpException('Produsul nu a fost găsit.');
        }

        $notificationRequest = (new StockNotificationRequest())->setProduct($product);
        $form = $this->createForm(StockNotificationRequestType::class, $notificationRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stockNotificationService->subscribe($product, $notificationRequest->getEmail());
            $this->addFlash('success', 'Te anunțăm pe email când produsul revine în stoc.');
        } else {
            $this->addFlash('error', 'Introdu o adresă de email validă.');
        }

        return $this->redirectToRoute('app_product_show', ['slug' => $slug]);
    }
}
