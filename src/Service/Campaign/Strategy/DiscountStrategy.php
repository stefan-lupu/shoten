<?php

namespace App\Service\Campaign\Strategy;

use App\Dto\CampaignDiscount;
use App\Entity\Campaign;
use App\Entity\Cart;
use App\Entity\Product;
use App\Enum\CampaignProductRole;
use App\Enum\CampaignType;
use App\Enum\DiscountValueType;

/**
 * Reducere (procent sau sumă fixă, vezi Campaign::$discountValueType) — pe
 * produse țintă (role=target) dacă sunt definite, altfel pe tot coșul.
 * Reducerea fixă nu poate depăși valoarea bazei pe care se aplică.
 */
final class DiscountStrategy implements CampaignStrategyInterface
{
    use CartLookupTrait;

    public function supports(Campaign $campaign): bool
    {
        return CampaignType::Discount === $campaign->getType();
    }

    public function evaluate(Cart $cart, Campaign $campaign): ?CampaignDiscount
    {
        $value = $campaign->getDiscountValue();
        $valueType = $campaign->getDiscountValueType();
        if (null === $value || null === $valueType) {
            return null;
        }

        $targets = $campaign->getProductsByRole(CampaignProductRole::Target);
        $base = $targets ? $this->getTargetsSubtotal($cart, $targets) : $this->getCartSubtotal($cart);

        if (bccomp($base, '0.00', 2) <= 0) {
            return null;
        }

        if (DiscountValueType::Percentage === $valueType) {
            $amount = bcdiv(bcmul($base, $value, 4), '100', 2);
            $description = sprintf('%s: −%s%% (%s lei)', $campaign->getName(), rtrim(rtrim($value, '0'), '.'), $amount);
        } else {
            $amount = bccomp($value, $base, 2) > 0 ? $base : $value;
            $description = sprintf('%s: −%s lei', $campaign->getName(), $amount);
        }

        if (bccomp($amount, '0.00', 2) <= 0) {
            return null;
        }

        return new CampaignDiscount(
            campaign: $campaign,
            amount: $amount,
            description: $description,
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
