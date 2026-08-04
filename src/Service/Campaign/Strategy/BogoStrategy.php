<?php

namespace App\Service\Campaign\Strategy;

use App\Dto\CampaignDiscount;
use App\Entity\Campaign;
use App\Entity\Cart;
use App\Enum\CampaignProductRole;
use App\Enum\CampaignType;

/**
 * BOGO simplu (1+1 gratis): definește un produs `trigger` (trebuie adăugat
 * manual în coș de client) și unul `gift`.
 *
 * Dacă trigger și gift sunt același produs („cumperi 2, plătești 1"):
 * gratuite = floor(cantitate din coș / 2) — reducere pe exemplarele deja
 * din coș, fără linie nouă.
 *
 * Dacă sunt produse diferite: clientul NU trebuie să adauge manual cadoul —
 * apare automat, câte un exemplar gratuit pentru fiecare exemplar din
 * declanșator (linie informativă în coș/checkout, devine OrderItem cu
 * preț 0 la finalizarea comenzii — vezi CampaignDiscount, OrderService).
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
            if ($freeUnits <= 0) {
                return null;
            }

            $amount = bcmul($triggerItem->getUnitPrice(), (string) $freeUnits, 2);

            return new CampaignDiscount(
                campaign: $campaign,
                amount: $amount,
                description: sprintf('%s: %d× %s gratis (−%s lei)', $campaign->getName(), $freeUnits, $gift->getName(), $amount),
            );
        }

        $freeUnits = $triggerItem->getQuantity();
        $amount = bcmul($gift->getPrice(), (string) $freeUnits, 2);

        return new CampaignDiscount(
            campaign: $campaign,
            amount: '0.00',
            description: sprintf('%s: %d× %s cadou (valoare %s lei)', $campaign->getName(), $freeUnits, $gift->getName(), $amount),
            freeGiftProduct: $gift,
            freeGiftQuantity: $freeUnits,
        );
    }
}
