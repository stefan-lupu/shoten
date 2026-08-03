<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cont/comenzi')]
#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    #[Route('', name: 'app_order_index')]
    public function index(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findByUser($user),
        ]);
    }

    #[Route('/{id}', name: 'app_order_show')]
    public function show(Order $order): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($order->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Această comandă nu îți aparține.');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }
}
