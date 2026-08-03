<?php

namespace App\Controller;

use App\Service\Payment\CardPaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Endpoint public (apelat server-to-server de providerul de plată, nu de
 * browser) — autenticitatea vine din semnătura payload-ului, nu din sesiune.
 */
final class PaymentWebhookController extends AbstractController
{
    #[Route('/webhook/payment/netopia', name: 'app_payment_webhook', methods: ['POST'])]
    public function netopia(Request $request, CardPaymentService $cardPaymentService): Response
    {
        $cardPaymentService->handleWebhook($request);

        return new Response('OK');
    }
}
