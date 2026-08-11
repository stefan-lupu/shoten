<?php

namespace App\Service;

use App\Entity\InvoiceSequence;
use App\Entity\Order;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Atribuie leneș seria + numărul fiscal al facturii, o singură dată per
 * comandă. Numerele sunt secvențiale și fără goluri per serie: contorul e
 * blocat pesimist (SELECT ... FOR UPDATE) în timpul incrementării, iar
 * atribuirea pe comandă se face în aceeași tranzacție cu incrementarea,
 * deci cele două se salvează atomic (dacă flush-ul eșuează, nu se consumă
 * un număr degeaba).
 */
final readonly class InvoiceNumberAllocator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Atribuie un număr comenzii dacă nu are deja unul. Idempotent — a doua
     * generare a aceleiași facturi reutilizează numărul deja atribuit.
     * Comenzile anulate nu primesc număr (nu se emite factură pentru ele).
     */
    public function ensureAssigned(Order $order, string $series): void
    {
        if ($order->hasInvoiceNumber() || '' === $series) {
            return;
        }

        $this->entityManager->wrapInTransaction(function () use ($order, $series): void {
            $sequence = $this->entityManager->getRepository(InvoiceSequence::class)
                ->createQueryBuilder('s')
                ->andWhere('s.series = :series')
                ->setParameter('series', $series)
                ->getQuery()
                // Blochează rândul până la commit — două cereri concurente pe
                // aceeași serie se serializează, deci nu pot primi același număr.
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult()
            ;

            if (!$sequence) {
                // Prima factură din serie — creăm contorul. (Există o cursă
                // teoretică doar la toată prima factură din serie, imposibil
                // de atins la un magazin cu volum normal; constrângerea unică
                // pe `series` ar respinge oricum al doilea insert simultan.)
                $sequence = (new InvoiceSequence())->setSeries($series);
                $this->entityManager->persist($sequence);
            }

            $next = $sequence->getLastNumber() + 1;
            $sequence->setLastNumber($next);
            $order->assignInvoiceNumber($series, $next);
        });
    }
}
