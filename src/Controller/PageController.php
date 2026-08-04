<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageController extends AbstractController
{
    #[Route('/confidentialitate', name: 'app_privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('page/privacy_policy.html.twig');
    }

    #[Route('/termeni-si-conditii', name: 'app_terms')]
    public function terms(): Response
    {
        return $this->render('page/terms.html.twig');
    }

    #[Route('/politica-retur', name: 'app_return_policy')]
    public function returnPolicy(): Response
    {
        return $this->render('page/return_policy.html.twig');
    }
}
