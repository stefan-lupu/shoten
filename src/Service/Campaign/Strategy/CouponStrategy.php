<?php

namespace App\Service\Campaign\Strategy;

use App\Dto\CampaignDiscount;
use App\Entity\Campaign;
use App\Entity\Cart;
use App\Enum\CampaignType;

/**
 * Cupon — reducere fixă (lei) pe tot coșul, activată doar de un cod
 * introdus explicit de client. Validarea codului (activ, interval,
 * maxUses) se face în App\Service\CampaignEngine, nu aici — strategia
 * presupune că a primit deja o campanie de cupon validă.
 */
final class CouponStrategy implements CampaignStrategyInterface
{
    use CartLookupTrait;

    public function supports(Campaign $campaign): bool
    {
        return CampaignType::Coupon === $campaign->getType();
    }

    public function evaluate(Cart $cart, Campaign $campaign): ?CampaignDiscount
    {
        $value = $campaign->getDiscountValue();
        if (null === $value) {
            return null;
        }

        $base = $this->getCartSubtotal($cart);
        if (bccomp($base, '0.00', 2) <= 0) {
            return null;
        }

        $amount = bccomp($value, $base, 2) > 0 ? $base : $value;

        return new CampaignDiscount(
            campaign: $campaign,
            amount: $amount,
            description: sprintf('Cod %s: −%s lei', $campaign->getCouponCode(), $amount),
        );
    }
}
