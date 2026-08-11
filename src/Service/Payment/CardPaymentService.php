<?php

namespace App\Service\Payment;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use App\Repository\OrderRepository;
use App\Service\StoreConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Plată cu cardul — interfață stabilă (createPaymentSession/handleWebhook)
 * indiferent de provider.
 *
 * NOTĂ: până la conectarea unui cont real Netopia/Stripe (NETOPIA_API_KEY
 * gol în .env.local), createPaymentSession() redirecționează către o
 * „poartă” simulată internă (PaymentSimulatorController) în loc de API-ul
 * real al providerului. handleWebhook() și verificarea semnăturii sunt
 * însă implementarea reală, neschimbată — la conectarea unui provider
 * real se schimbă doar modul în care e generat payload-ul webhook-ului.
 */
final class CardPaymentService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $webhookSecret,
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderRepository $orderRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MailerInterface $mailer,
        private readonly StoreConfig $store,
    ) {
    }

    public function isSimulated(): bool
    {
        return '' === $this->apiKey;
    }

    public function createPaymentSession(Order $order): string
    {
        $reference = bin2hex(random_bytes(16));
        $order->setPaymentReference($reference);
        $this->entityManager->flush();

        if ($this->isSimulated()) {
            return $this->urlGenerator->generate('app_payment_simulate', ['reference' => $reference], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        // Aici, cu un provider real, s-ar apela API-ul Netopia/Stripe cu
        // $this->apiKey pentru a crea sesiunea și a obține URL-ul de redirect.
        throw new \LogicException('Provider de plată real neconfigurat.');
    }

    public function handleWebhook(Request $request): void
    {
        $payload = $request->request->all();
        $signature = (string) $request->headers->get('X-Payment-Signature');

        if (!$this->verifySignature($payload, $signature)) {
            throw new AccessDeniedHttpException('Semnătură webhook invalidă.');
        }

        $reference = $payload['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (!\is_string($reference) || !\is_string($status)) {
            throw new AccessDeniedHttpException('Payload webhook invalid.');
        }

        $order = $this->orderRepository->findOneByPaymentReference($reference);
        if (!$order) {
            throw new NotFoundHttpException('Comanda nu a fost găsită pentru referința primită.');
        }

        if ('paid' === $status) {
            $order->setPaymentStatus(PaymentStatus::Paid);
            $order->setStatus(OrderStatus::Confirmed);
            $this->entityManager->flush();
            $this->sendPaymentConfirmedEmail($order);
        } else {
            $order->setPaymentStatus(PaymentStatus::Failed);
            $this->entityManager->flush();
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function signPayload(array $payload): string
    {
        return hash_hmac('sha256', http_build_query($payload), $this->webhookSecret);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function verifySignature(array $payload, string $signature): bool
    {
        if ('' === $signature || '' === $this->webhookSecret) {
            return false;
        }

        return hash_equals($this->signPayload($payload), $signature);
    }

    private function sendPaymentConfirmedEmail(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new EmailAddress($this->store->email, $this->store->name))
            ->to($order->getContactEmail())
            ->subject(sprintf('Plata pentru comanda #%d a fost confirmată', $order->getId()))
            ->htmlTemplate('emails/payment_confirmed.html.twig')
            ->context(['order' => $order])
        ;

        $this->mailer->send($email);
    }
}
