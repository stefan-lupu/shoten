<?php

namespace App\Controller;

use App\Dto\CheckoutData;
use App\Entity\User;
use App\Enum\PaymentMethod;
use App\Exception\InsufficientStockException;
use App\Exception\WholesaleMinimumNotMetException;
use App\Form\CheckoutType;
use App\Repository\AddressRepository;
use App\Service\CampaignEngine;
use App\Service\CartManager;
use App\Service\OrderService;
use App\Service\Payment\CardPaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
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
        /** @var User $user */
        $user = $this->getUser();
        $cart = $cartManager->getCurrentCart();

        if ($cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Coșul este gol.');

            return $this->redirectToRoute('app_cart');
        }

        $addresses = $addressRepository->findByUser($user);
        if (!$addresses) {
            $this->addFlash('error', 'Adaugă mai întâi o adresă de livrare.');

            return $this->redirectToRoute('app_address_new', ['redirect_to' => 'checkout']);
        }

        $couponCode = $request->getSession()->get(CartController::COUPON_SESSION_KEY);

        $data = new CheckoutData();
        $data->address = $addresses[0]; // findByUser sortează implicit adresa principală prima.

        $form = $this->createForm(CheckoutType::class, $data, ['addresses' => $addresses]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $order = $orderService->placeOrder($user, $cart, $data, $couponCode);
                $request->getSession()->remove(CartController::COUPON_SESSION_KEY);

                if (PaymentMethod::Card === $order->getPaymentMethod()) {
                    return new RedirectResponse($cardPaymentService->createPaymentSession($order));
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
            'cart' => $cart,
            'campaignResult' => $campaignEngine->applyCampaigns($cart, $couponCode),
        ]);
    }
}
