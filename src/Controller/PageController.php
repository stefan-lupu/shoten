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
}
