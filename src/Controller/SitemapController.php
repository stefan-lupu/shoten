<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\StoreConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SitemapController extends AbstractController
{
    /**
     * Dinamic (nu fișier static în public/) ca să nu trebuiască editat
     * manual la fiecare clonare — domeniul vine din store.domain.
     */
    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'])]
    public function robots(StoreConfig $store): Response
    {
        $content = sprintf(
            "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /cont\nDisallow: /cos\nDisallow: /checkout\n\nSitemap: https://%s/sitemap.xml\n",
            $store->domain,
        );

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $response;
    }

    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'])]
    public function sitemap(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        CacheInterface $cache,
    ): Response {
        $xml = $cache->get('sitemap_xml', function (ItemInterface $item) use ($categoryRepository, $productRepository) {
            // Regenerat cel mult o dată pe oră — un crawler nu are nevoie de date
            // mai proaspete decât atât, iar recalcularea la fiecare vizită ar
            // interoga inutil toate produsele și categoriile de fiecare dată.
            $item->expiresAfter(3600);

            $urls = [];

            $urls[] = [
                'loc' => $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ];
            foreach (['app_privacy_policy', 'app_terms', 'app_return_policy', 'app_contact'] as $staticRoute) {
                $urls[] = [
                    'loc' => $this->generateUrl($staticRoute, [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'changefreq' => 'yearly',
                    'priority' => '0.3',
                ];
            }

            foreach ($categoryRepository->findAll() as $category) {
                $urls[] = [
                    'loc' => $this->generateUrl('app_category_show', ['slug' => $category->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }

            foreach ($productRepository->findAll() as $product) {
                $urls[] = [
                    'loc' => $this->generateUrl('app_product_show', ['slug' => $product->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                    'lastmod' => $product->getCreatedAt()?->format('c'),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }

            return $this->render('sitemap/sitemap.xml.twig', ['urls' => $urls])->getContent();
        });

        $response = new Response($xml);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
