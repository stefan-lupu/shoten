<?php

namespace App\Controller\Admin;

use App\Enum\ReviewStatus;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Service\StoreConfig;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    private const int LOW_STOCK_THRESHOLD = 10;

    public function __construct(
        private readonly StoreConfig $store,
        private readonly OrderRepository $orderRepository,
        private readonly ReviewRepository $reviewRepository,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function index(): Response
    {
        $since = new \DateTimeImmutable('-7 days');

        return $this->render('admin/dashboard.html.twig', [
            'recentOrdersCount' => $this->orderRepository->countSince($since),
            'pendingReviewsCount' => $this->reviewRepository->count(['status' => ReviewStatus::Pending]),
            'lowStockProducts' => $this->productRepository->findLowStock(self::LOW_STOCK_THRESHOLD),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        $title = htmlspecialchars($this->store->name, ENT_QUOTES);
        if ($this->store->logoPath) {
            $title = sprintf('<img src="%s" alt="%s" style="max-height: 24px; margin-right: 8px">%s', $this->store->logoPath, $title, $title);
        }

        return Dashboard::new()
            ->setTitle($title)
            ->setFaviconPath($this->store->faviconPath ?: '/favicon.ico')
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Catalog');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categorii', 'fa fa-sitemap');
        yield MenuItem::linkTo(ProductCrudController::class, 'Produse', 'fa fa-box');
        yield MenuItem::linkTo(ReviewCrudController::class, 'Recenzii', 'fa fa-star');

        yield MenuItem::section('Vânzări');
        yield MenuItem::linkTo(OrderCrudController::class, 'Comenzi', 'fa fa-receipt');
        yield MenuItem::linkTo(CampaignCrudController::class, 'Campanii', 'fa fa-tags');
        yield MenuItem::linkTo(CampaignProductCrudController::class, 'Produse în campanii', 'fa fa-tag');

        yield MenuItem::section('Marketing');
        yield MenuItem::linkTo(NewsletterSubscriberCrudController::class, 'Abonați newsletter', 'fa fa-envelope');

        yield MenuItem::section();
        yield MenuItem::linkToUrl('Vezi magazinul', 'fa fa-external-link-alt', '/');
    }
}
