<?php

namespace App\Service\Campaign\Strategy;

use App\Dto\CampaignDiscount;
use App\Entity\Campaign;
use App\Entity\Cart;
use App\Enum\CampaignProductRole;
use App\Enum\CampaignType;

/**
 * Reducere fixă (lei) — pe produse țintă dacă sunt definite (cel puțin
 * unul trebuie să fie în coș), altfel pe tot coșul. Nu poate depăși
 * valoarea bazei pe care se aplică.
 */
final class FixedDiscountStrategy implements CampaignStrategyInterface
{
    use CartLookupTrait;

    public function supports(Campaign $campaign): bool
    {
        return CampaignType::FixedDiscount === $campaign->getType();
    }

    public function evaluate(Cart $cart, Campaign $campaign): ?CampaignDiscount
    {
        $value = $campaign->getDiscountValue();
        if (null === $value) {
            return null;
        }

        $targets = $campaign->getProductsByRole(CampaignProductRole::Target);

        if ($targets) {
            $base = '0.00';
            foreach ($targets as $target) {
                $item = $this->findCartItem($cart, $target);
                if ($item) {
                    $base = bcadd($base, $item->getSubtotal(), 2);
                }
            }
            if (bccomp($base, '0.00', 2) <= 0) {
                return null;
            }
        } else {
            $base = $this->getCartSubtotal($cart);
            if (bccomp($base, '0.00', 2) <= 0) {
                return null;
            }
        }

        $amount = bccomp($value, $base, 2) > 0 ? $base : $value;

        return new CampaignDiscount(
            campaign: $campaign,
            amount: $amount,
            description: sprintf('%s: −%s lei', $campaign->getName(), $amount),
        );
    }
}
