<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\StockStatus;
use App\Exception\InsufficientStockException;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final class CartManager
{
    private const string SESSION_KEY = 'cart_session_id';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartRepository $cartRepository,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function getCurrentCart(): Cart
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $cart = $this->cartRepository->findOneBy(['user' => $user]);
            if (!$cart) {
                $cart = (new Cart())->setUser($user);
                $this->entityManager->persist($cart);
                $this->entityManager->flush();
            }

            return $cart;
        }

        $sessionId = $this->getOrCreateSessionId();
        $cart = $this->cartRepository->findOneBy(['sessionId' => $sessionId]);
        if (!$cart) {
            $cart = (new Cart())->setSessionId($sessionId);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        return $cart;
    }

    /**
     * @throws InsufficientStockException
     */
    public function addItem(Product $product, int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Cantitatea trebuie să fie cel puțin 1.');
        }

        $cart = $this->getCurrentCart();
        $item = $this->findItem($cart, $product);
        $newQuantity = $quantity + ($item?->getQuantity() ?? 0);
        $this->assertStockAvailable($product, $newQuantity);

        if ($item) {
            $item->setQuantity($newQuantity);
        } else {
            $item = (new CartItem())
                ->setCart($cart)
                ->setProduct($product)
                ->setQuantity($quantity)
                ->setUnitPrice($product->getPrice())
            ;
            $this->entityManager->persist($item);
        }

        $cart->touch();
        $this->entityManager->flush();
    }

    /**
     * @throws InsufficientStockException
     */
    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            $this->removeItem($item);

            return;
        }

        $this->assertStockAvailable($item->getProduct(), $quantity);
        $item->setQuantity($quantity);
        $item->getCart()->touch();
        $this->entityManager->flush();
    }

    public function removeItem(CartItem $item): void
    {
        $cart = $item->getCart();
        $this->entityManager->remove($item);
        $cart->touch();
        $this->entityManager->flush();
    }

    public function getTotal(Cart $cart): string
    {
        $total = '0.00';
        foreach ($cart->getItems() as $item) {
            $total = bcadd($total, $item->getSubtotal(), 2);
        }

        return $total;
    }

    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->getCurrentCart()->getItems() as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }

    /**
     * Apelat la login: combină coșul de sesiune (dacă există) cu cel al
     * userului, fără să piardă produse din niciunul dintre ele.
     */
    public function mergeSessionCartIntoUserCart(User $user): void
    {
        $sessionId = $this->requestStack->getSession()->get(self::SESSION_KEY);
        if (!$sessionId) {
            return;
        }

        $sessionCart = $this->cartRepository->findOneBy(['sessionId' => $sessionId]);
        if (!$sessionCart || $sessionCart->getItems()->isEmpty()) {
            return;
        }

        $userCart = $this->cartRepository->findOneBy(['user' => $user]);
        if (!$userCart) {
            $sessionCart->setUser($user);
            $sessionCart->setSessionId(null);
            $this->entityManager->flush();

            return;
        }

        foreach ($sessionCart->getItems()->toArray() as $sessionItem) {
            // scoatem itemul din colecția vechiului coș înainte de a-l realoca,
            // altfel orphanRemoval îl șterge la flush (nu mai apare ca membru al niciunei colecții)
            $sessionCart->getItems()->removeElement($sessionItem);

            $existing = $this->findItem($userCart, $sessionItem->getProduct());
            if ($existing) {
                $existing->setQuantity($existing->getQuantity() + $sessionItem->getQuantity());
                $this->entityManager->remove($sessionItem);
            } else {
                $sessionItem->setCart($userCart);
                $userCart->getItems()->add($sessionItem);
            }
        }

        $this->entityManager->remove($sessionCart);
        $userCart->touch();
        $this->entityManager->flush();
    }

    private function getOrCreateSessionId(): string
    {
        $session = $this->requestStack->getSession();
        $sessionId = $session->get(self::SESSION_KEY);
        if (!$sessionId) {
            $sessionId = bin2hex(random_bytes(16));
            $session->set(self::SESSION_KEY, $sessionId);
        }

        return $sessionId;
    }

    private function findItem(Cart $cart, Product $product): ?CartItem
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct() === $product) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @throws InsufficientStockException
     */
    private function assertStockAvailable(Product $product, int $quantity): void
    {
        if ($product->getStockStatus() === StockStatus::InStock && $quantity > $product->getStock()) {
            throw new InsufficientStockException(sprintf('Stoc insuficient. Disponibil: %d buc.', $product->getStock()));
        }
    }
}
