<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Exception\InsufficientStockException;
use App\Service\CampaignEngine;
use App\Service\CartManager;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public const string COUPON_SESSION_KEY = 'cart_coupon_code';

    #[Route('/cos', name: 'app_cart', methods: ['GET'])]
    public function show(Request $request, CartManager $cartManager, CampaignEngine $campaignEngine, OrderService $orderService): Response
    {
        $cart = $cartManager->getCurrentCart();
        $couponCode = $request->getSession()->get(self::COUPON_SESSION_KEY);
        $campaignResult = $campaignEngine->applyCampaigns($cart, $couponCode);

        // Avertisment informativ pentru clienții angro care nu ating încă
        // pragul minim — ca să nu fie surprinși abia la plasarea comenzii
        // (null pentru retail sau când pragul e atins).
        $user = $this->getUser();
        $wholesaleMinimumWarning = $user instanceof User && !$cart->getItems()->isEmpty()
            ? $orderService->wholesaleMinimumError($user, $cart, $campaignResult->subtotal)
            : null;

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'campaignResult' => $campaignResult,
            'appliedCouponCode' => $couponCode,
            'wholesaleMinimumWarning' => $wholesaleMinimumWarning,
        ]);
    }

    #[Route('/cos/cupon', name: 'app_cart_apply_coupon', methods: ['POST'])]
    public function applyCoupon(Request $request): RedirectResponse
    {
        $this->assertCsrfToken($request);
        $code = trim((string) $request->request->get('coupon_code'));

        if ('' === $code) {
            $request->getSession()->remove(self::COUPON_SESSION_KEY);
        } else {
            $request->getSession()->set(self::COUPON_SESSION_KEY, $code);
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cos/cupon/elimina', name: 'app_cart_remove_coupon', methods: ['POST'])]
    public function removeCoupon(Request $request): RedirectResponse
    {
        $this->assertCsrfToken($request);
        $request->getSession()->remove(self::COUPON_SESSION_KEY);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cos/adauga/{product}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request, CartManager $cartManager): RedirectResponse
    {
        $this->assertCsrfToken($request);
        $quantity = max(1, $request->request->getInt('quantity', 1));

        try {
            $cartManager->addItem($product, $quantity);
            $this->addFlash('success', sprintf('„%s” a fost adăugat în coș.', $product->getName()));
        } catch (InsufficientStockException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_product_show', ['slug' => $product->getSlug()]);
    }

    #[Route('/cos/actualizeaza/{item}', name: 'app_cart_update', methods: ['POST'])]
    public function update(CartItem $item, Request $request, CartManager $cartManager): RedirectResponse
    {
        $this->assertCsrfToken($request);
        $this->assertOwnsItem($item, $cartManager);
        $quantity = $request->request->getInt('quantity', 1);

        try {
            $cartManager->updateQuantity($item, $quantity);
        } catch (InsufficientStockException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cos/elimina/{item}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(CartItem $item, Request $request, CartManager $cartManager): RedirectResponse
    {
        $this->assertCsrfToken($request);
        $this->assertOwnsItem($item, $cartManager);
        $cartManager->removeItem($item);
        $this->addFlash('success', 'Produsul a fost eliminat din coș.');

        return $this->redirectToRoute('app_cart');
    }

    private function assertCsrfToken(Request $request): void
    {
        if (!$this->isCsrfTokenValid('cart_action', $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Token CSRF invalid.');
        }
    }

    private function assertOwnsItem(CartItem $item, CartManager $cartManager): void
    {
        if ($item->getCart() !== $cartManager->getCurrentCart()) {
            throw new AccessDeniedHttpException('Acest produs din coș nu îți aparține.');
        }
    }
}
