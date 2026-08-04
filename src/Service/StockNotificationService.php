<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\StockNotificationRequest;
use App\Repository\StockNotificationRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;

final class StockNotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StockNotificationRequestRepository $repository,
        private readonly MailerInterface $mailer,
        private readonly StoreConfig $store,
    ) {
    }

    public function subscribe(Product $product, string $email): void
    {
        $existing = $this->entityManager->getRepository(StockNotificationRequest::class)
            ->findOneBy(['product' => $product, 'email' => $email, 'notifiedAt' => null]);
        if ($existing) {
            return;
        }

        $request = (new StockNotificationRequest())
            ->setProduct($product)
            ->setEmail($email)
        ;

        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    /**
     * Se apelează la fiecare salvare de produs din admin — trimite notificări
     * doar dacă stocul chiar a trecut din epuizat (0) în disponibil.
     */
    public function notifyIfBackInStock(Product $product, int $previousStock): void
    {
        if ($previousStock > 0 || $product->getStock() <= 0) {
            return;
        }

        $pending = $this->repository->findPendingForProduct($product);
        if (!$pending) {
            return;
        }

        foreach ($pending as $request) {
            $email = (new TemplatedEmail())
                ->from(new EmailAddress($this->store->email, $this->store->name))
                ->to($request->getEmail())
                ->subject(sprintf('„%s” a revenit în stoc — %s', $product->getName(), $this->store->name))
                ->htmlTemplate('emails/back_in_stock.html.twig')
                ->context(['product' => $product])
            ;
            $this->mailer->send($email);

            $request->markNotified();
        }

        $this->entityManager->flush();
    }
}
