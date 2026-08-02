<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('/cont', name: 'app_account')]
    public function index(): Response
    {
        return $this->render('account/index.html.twig');
    }
}
