<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Repository\OrderRepository;
use App\Service\InvoicePdfService;
use App\Service\OrderService;
use App\Service\Payment\CardPaymentService;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
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
    public function show(Order $order, Request $request, EntityManagerInterface $entityManager, StoreConfig $store): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($order->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Această comandă nu îți aparține.');
        }

        // Se trimite o singură dată per comandă (flag persistat), și doar
        // dacă userul și-a dat deja acordul pentru cookie-uri de publicitate —
        // altfel rămâne netrimis până la o vizită ulterioară cu consimțământ dat.
        $cookieConsentAccepted = 'accepted' === $request->cookies->get('cookie_consent');
        $fireAdsConversion = $cookieConsentAccepted && $store->googleAdsConversionId && null === $order->getAdsConversionSentAt();
        if ($fireAdsConversion) {
            $order->markAdsConversionSent();
            $entityManager->flush();
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
            'fireAdsConversion' => $fireAdsConversion,
        ]);
    }

    #[Route('/{id}/factura', name: 'app_order_invoice')]
    public function invoice(Order $order, InvoicePdfService $invoicePdfService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($order->getUser() !== $user && !$this->isGranted('ROLE_ORDERS_VIEWER')) {
            throw new AccessDeniedHttpException('Această comandă nu îți aparține.');
        }

        return new Response($invoicePdfService->generate($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="factura-comanda-%d.pdf"', $order->getId()),
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

    #[Route('/{id}/anuleaza', name: 'app_order_cancel', methods: ['POST'])]
    public function cancel(Order $order, Request $request, OrderService $orderService): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($order->getUser() !== $user) {
            throw new AccessDeniedHttpException('Această comandă nu îți aparține.');
        }

        if (!$this->isCsrfTokenValid('order_cancel_'.$order->getId(), $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Token CSRF invalid.');
        }

        try {
            $orderService->cancelOrder($order);
            $this->addFlash('success', 'Comanda a fost anulată.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }
}
