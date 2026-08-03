<?php

namespace App\Service\Campaign\Strategy;

use App\Dto\CampaignDiscount;
use App\Entity\Campaign;
use App\Entity\Cart;
use App\Enum\CampaignProductRole;
use App\Enum\CampaignType;
use App\Entity\Product;

/**
 * Reducere procentuală — pe produse țintă (role=target) dacă sunt definite,
 * altfel pe tot coșul (reducere generală de site).
 */
final class PercentageDiscountStrategy implements CampaignStrategyInterface
{
    use CartLookupTrait;

    public function supports(Campaign $campaign): bool
    {
        return CampaignType::PercentageDiscount === $campaign->getType();
    }

    public function evaluate(Cart $cart, Campaign $campaign): ?CampaignDiscount
    {
        $percent = $campaign->getDiscountValue();
        if (null === $percent) {
            return null;
        }

        $targets = $campaign->getProductsByRole(CampaignProductRole::Target);
        $base = $targets ? $this->getTargetsSubtotal($cart, $targets) : $this->getCartSubtotal($cart);

        if (bccomp($base, '0.00', 2) <= 0) {
            return null;
        }

        $amount = bcdiv(bcmul($base, $percent, 4), '100', 2);
        if (bccomp($amount, '0.00', 2) <= 0) {
            return null;
        }

        return new CampaignDiscount(
            campaign: $campaign,
            amount: $amount,
            description: sprintf('%s: −%s%% (%s lei)', $campaign->getName(), rtrim(rtrim($percent, '0'), '.'), $amount),
        );
    }

    /**
     * @param Product[] $targets
     */
    private function getTargetsSubtotal(Cart $cart, array $targets): string
    {
        $subtotal = '0.00';
        foreach ($targets as $target) {
            $item = $this->findCartItem($cart, $target);
            if ($item) {
                $subtotal = bcadd($subtotal, $item->getSubtotal(), 2);
            }
        }

        return $subtotal;
    }
}
