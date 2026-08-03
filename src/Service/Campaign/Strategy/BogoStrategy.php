<?php

namespace App\Service\Campaign\Strategy;

use App\Dto\CampaignDiscount;
use App\Entity\Campaign;
use App\Entity\Cart;
use App\Enum\CampaignProductRole;
use App\Enum\CampaignType;

/**
 * BOGO simplu (1+1 gratis): definește un produs `trigger` și unul `gift`
 * (pot fi același produs, pentru „cumperi 2, plătești 1"). Pentru fiecare
 * pereche trigger+gift din coș, un exemplar din gift devine gratis.
 *
 * Dacă trigger și gift sunt același produs: gratuite = floor(cantitate / 2).
 * Dacă sunt produse diferite: gratuite = min(cantitate trigger, cantitate gift).
 */
final class BogoStrategy implements CampaignStrategyInterface
{
    use CartLookupTrait;

    public function supports(Campaign $campaign): bool
    {
        return CampaignType::Bogo === $campaign->getType();
    }

    public function evaluate(Cart $cart, Campaign $campaign): ?CampaignDiscount
    {
        $trigger = $campaign->getProductsByRole(CampaignProductRole::Trigger)[0] ?? null;
        $gift = $campaign->getProductsByRole(CampaignProductRole::Gift)[0] ?? null;

        if (!$trigger || !$gift) {
            return null;
        }

        $triggerItem = $this->findCartItem($cart, $trigger);
        if (!$triggerItem) {
            return null;
        }

        if ($trigger === $gift) {
            $freeUnits = intdiv($triggerItem->getQuantity(), 2);
            $giftUnitPrice = $triggerItem->getUnitPrice();
        } else {
            $giftItem = $this->findCartItem($cart, $gift);
            if (!$giftItem) {
                return null;
            }
            $freeUnits = min($triggerItem->getQuantity(), $giftItem->getQuantity());
            $giftUnitPrice = $giftItem->getUnitPrice();
        }

        if ($freeUnits <= 0) {
            return null;
        }

        $amount = bcmul($giftUnitPrice, (string) $freeUnits, 2);

        return new CampaignDiscount(
            campaign: $campaign,
            amount: $amount,
            description: sprintf('%s: %d× %s gratis (−%s lei)', $campaign->getName(), $freeUnits, $gift->getName(), $amount),
        );
    }
}
