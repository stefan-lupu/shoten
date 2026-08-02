<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\CartManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
final readonly class CartMergeListener
{
    public function __construct(private CartManager $cartManager)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            $this->cartManager->mergeSessionCartIntoUserCart($user);
        }
    }
}
