<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Repository\OrderRepository;
use App\Service\Payment\CardPaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cont/comenzi')]
#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    #[Route('', name: 'app_order_index')]
    public function index(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findByUser($user),
        ]);
    }

    #[Route('/{id}', name: 'app_order_show')]
    public function show(Order $order): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($order->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Această comandă nu îți aparține.');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/reincearca-plata', name: 'app_order_retry_payment', methods: ['POST'])]
    public function retryPayment(Order $order, Request $request, CardPaymentService $cardPaymentService): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($order->getUser() !== $user) {
            throw new AccessDeniedHttpException('Această comandă nu îți aparține.');
        }

        if (!$this->isCsrfTokenValid('order_retry_payment_'.$order->getId(), $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Token CSRF invalid.');
        }

        if (PaymentMethod::Card !== $order->getPaymentMethod() || PaymentStatus::Paid === $order->getPaymentStatus()) {
            throw new AccessDeniedHttpException('Această comandă nu poate fi replătită.');
        }

        return new RedirectResponse($cardPaymentService->createPaymentSession($order));
    }
}
