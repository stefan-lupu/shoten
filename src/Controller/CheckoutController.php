<?php

namespace App\Controller;

use App\Dto\CheckoutData;
use App\Entity\User;
use App\Enum\PaymentMethod;
use App\Exception\InsufficientStockException;
use App\Form\CheckoutType;
use App\Repository\AddressRepository;
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
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $cart = $cartManager->getCurrentCart();

        if ($cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Coșul este gol.');

            return $this->redirectToRoute('app_cart');
        }

        $defaultAddress = $addressRepository->findOneBy(['user' => $user, 'isDefault' => true]);
        $data = CheckoutData::fromAddress($defaultAddress);

        $form = $this->createForm(CheckoutType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $order = $orderService->placeOrder($user, $cart, $data);

                if (PaymentMethod::Card === $order->getPaymentMethod()) {
                    return new RedirectResponse($cardPaymentService->createPaymentSession($order));
                }

                return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
            } catch (InsufficientStockException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('checkout/index.html.twig', [
            'form' => $form,
            'cart' => $cart,
            'total' => $cartManager->getTotal($cart),
        ]);
    }
}
