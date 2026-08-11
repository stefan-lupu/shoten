<?php

namespace App\Controller;

use App\Dto\CheckoutData;
use App\Entity\User;
use App\Enum\PaymentMethod;
use App\Exception\InsufficientStockException;
use App\Exception\WholesaleMinimumNotMetException;
use App\Form\CheckoutType;
use App\Form\GuestCheckoutType;
use App\Repository\AddressRepository;
use App\Repository\OrderRepository;
use App\Service\CampaignEngine;
use App\Service\CartManager;
use App\Service\OrderService;
use App\Service\Payment\CardPaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

// Fără #[IsGranted] — checkout-ul e accesibil și vizitatorilor (guest checkout).
final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CartManager $cartManager,
        OrderService $orderService,
        AddressRepository $addressRepository,
        CardPaymentService $cardPaymentService,
        CampaignEngine $campaignEngine,
    ): Response {
        $user = $this->getUser();
        $isGuest = !$user instanceof User;
        $cart = $cartManager->getCurrentCart();

        if ($cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Coșul este gol.');

            return $this->redirectToRoute('app_cart');
        }

        $couponCode = $request->getSession()->get(CartController::COUPON_SESSION_KEY);
        $data = new CheckoutData();

        if ($isGuest) {
            $form = $this->createForm(GuestCheckoutType::class, $data);
        } else {
            $addresses = $addressRepository->findByUser($user);
            if (!$addresses) {
                $this->addFlash('error', 'Adaugă mai întâi o adresă de livrare.');

                return $this->redirectToRoute('app_address_new', ['redirect_to' => 'checkout']);
            }
            $data->address = $addresses[0]; // findByUser sortează implicit adresa principală prima.
            $form = $this->createForm(CheckoutType::class, $data, ['addresses' => $addresses]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $order = $orderService->placeOrder($isGuest ? null : $user, $cart, $data, $couponCode);
                $request->getSession()->remove(CartController::COUPON_SESSION_KEY);

                if (PaymentMethod::Card === $order->getPaymentMethod()) {
                    // Cardul apare doar în formularul cu cont, deci $order are user aici.
                    return new RedirectResponse($cardPaymentService->createPaymentSession($order));
                }

                if ($isGuest) {
                    return $this->redirectToRoute('app_order_guest_confirmation', ['token' => $order->getGuestToken()]);
                }

                return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
            } catch (InsufficientStockException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (WholesaleMinimumNotMetException $e) {
                // Comanda nu atinge pragul minim angro — înapoi la coș, unde
                // clientul poate ajusta cantitățile (vezi mesajul informativ).
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('app_cart');
            }
        }

        return $this->render('checkout/index.html.twig', [
            'form' => $form,
            'isGuest' => $isGuest,
            'cart' => $cart,
            'campaignResult' => $campaignEngine->applyCampaigns($cart, $couponCode),
        ]);
    }

    /**
     * Pagina de confirmare pentru o comandă guest — publică, dar accesibilă
     * doar cu tokenul aleator generat la plasare (guestul nu se poate loga
     * să vadă /cont/comenzi). Tokenul e secretul; fără el, 404.
     */
    #[Route('/comanda/confirmare/{token}', name: 'app_order_guest_confirmation', methods: ['GET'])]
    public function guestConfirmation(string $token, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findOneBy(['guestToken' => $token]);
        if (!$order) {
            throw new NotFoundHttpException('Comandă inexistentă.');
        }

        return $this->render('checkout/guest_confirmation.html.twig', ['order' => $order]);
    }
}
