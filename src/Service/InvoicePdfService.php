<?php

namespace App\Service;

use App\Entity\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class InvoicePdfService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly StoreConfig $store,
    ) {
    }

    public function generate(Order $order): string
    {
        $html = $this->twig->render('invoice/pdf.html.twig', [
            'order' => $order,
            'store' => $this->store,
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
