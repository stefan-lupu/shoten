<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\Payment\CardPaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Poartă de plată SIMULATĂ — stă în locul paginii găzduite de
 * Netopia/Stripe cât timp nu există un cont real conectat (vezi
 * App\Service\Payment\CardPaymentService::isSimulated()). Nu există
 * echivalent în producție cu un provider real.
 */
#[IsGranted('ROLE_USER')]
final class PaymentSimulatorController extends AbstractController
{
    #[Route('/checkout/plata-simulata/{reference}', name: 'app_payment_simulate')]
    public function show(string $reference, OrderRepository $orderRepository, CardPaymentService $cardPaymentService): Response
    {
        $order = $orderRepository->findOneByPaymentReference($reference);
        if (!$order) {
            throw new NotFoundHttpException('Sesiune de plată invalidă.');
        }

        if ($order->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('Această comandă nu îți aparține.');
        }

        $successPayload = ['reference' => $reference, 'status' => 'paid'];
        $failPayload = ['reference' => $reference, 'status' => 'failed'];

        return $this->render('payment/simulate.html.twig', [
            'order' => $order,
            'webhookUrl' => $this->generateUrl('app_payment_webhook'),
            'successPayload' => $successPayload,
            'successSignature' => $cardPaymentService->signPayload($successPayload),
            'failPayload' => $failPayload,
            'failSignature' => $cardPaymentService->signPayload($failPayload),
        ]);
    }
}
