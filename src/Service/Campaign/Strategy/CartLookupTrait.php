<?php

namespace App\Service\Campaign\Strategy;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;

trait CartLookupTrait
{
    private function findCartItem(Cart $cart, Product $product): ?CartItem
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct() === $product) {
                return $item;
            }
        }

        return null;
    }

    private function getCartSubtotal(Cart $cart): string
    {
        $subtotal = '0.00';
        foreach ($cart->getItems() as $item) {
            $subtotal = bcadd($subtotal, $item->getSubtotal(), 2);
        }

        return $subtotal;
    }
}
