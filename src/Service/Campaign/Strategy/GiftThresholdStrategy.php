<?php

namespace App\Service\Campaign\Strategy;

use App\Dto\CampaignDiscount;
use App\Entity\Campaign;
use App\Entity\Cart;
use App\Enum\CampaignProductRole;
use App\Enum\CampaignType;

/**
 * Produs cadou la prag valoric: dacă subtotalul coșului atinge
 * `discountValue` (pragul, în lei) ȘI produsul `gift` e deja în coș,
 * un exemplar din el devine gratis. Motorul nu adaugă automat produsul
 * cadou în coș — clientul trebuie să-l fi pus deja.
 */
final class GiftThresholdStrategy implements CampaignStrategyInterface
{
    use CartLookupTrait;

    public function supports(Campaign $campaign): bool
    {
        return CampaignType::GiftThreshold === $campaign->getType();
    }

    public function evaluate(Cart $cart, Campaign $campaign): ?CampaignDiscount
    {
        $threshold = $campaign->getDiscountValue();
        $gift = $campaign->getProductsByRole(CampaignProductRole::Gift)[0] ?? null;

        if (null === $threshold || !$gift) {
            return null;
        }

        $subtotal = $this->getCartSubtotal($cart);
        if (bccomp($subtotal, $threshold, 2) < 0) {
            return null;
        }

        $giftItem = $this->findCartItem($cart, $gift);
        if (!$giftItem) {
            return null;
        }

        $amount = $giftItem->getUnitPrice();

        return new CampaignDiscount(
            campaign: $campaign,
            amount: $amount,
            description: sprintf('%s: cadou %s la comenzi peste %s lei (−%s lei)', $campaign->getName(), $gift->getName(), $threshold, $amount),
        );
    }
}
