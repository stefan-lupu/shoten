<?php

namespace App\Service;

use App\Entity\Order;
use App\Enum\OrderStatus;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class InvoicePdfService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly StoreConfig $store,
        private readonly InvoiceNumberAllocator $invoiceNumberAllocator,
    ) {
    }

    public function generate(Order $order): string
    {
        // Emiterea facturii = prima ei generare: atribuim seria + numărul
        // fiscal acum, o singură dată (comenzile anulate nu primesc factură).
        if (OrderStatus::Cancelled !== $order->getStatus()) {
            $this->invoiceNumberAllocator->ensureAssigned($order, $this->store->invoiceSeries);
        }

        // TVA e mereu inclus în prețul afișat (nu se adaugă separat) — aici doar
        // calculăm defalcarea informativă, cerută pe factură dacă firma e plătitoare.
        $vatAmount = null;
        $netAmount = null;
        if ($this->store->vatPayer && $this->store->vatRate) {
            $divisor = bcadd('100', $this->store->vatRate, 4);
            $netAmount = bcdiv(bcmul($order->getTotal(), '100', 4), $divisor, 2);
            $vatAmount = bcsub($order->getTotal(), $netAmount, 2);
        }

        $html = $this->twig->render('invoice/pdf.html.twig', [
            'order' => $order,
            'store' => $this->store,
            'vatAmount' => $vatAmount,
            'netAmount' => $netAmount,
        ]);

        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
