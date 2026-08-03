<?php

namespace App\Service\Campaign\Strategy;

use App\Dto\CampaignDiscount;
use App\Entity\Campaign;
use App\Entity\Cart;
use App\Enum\CampaignProductRole;
use App\Enum\CampaignType;

/**
 * Bundle: reducere fixă (lei) aplicată doar dacă TOATE produsele marcate
 * `bundle_item` sunt prezente în coș (cel puțin câte un exemplar din fiecare).
 */
final class BundleStrategy implements CampaignStrategyInterface
{
    use CartLookupTrait;

    public function supports(Campaign $campaign): bool
    {
        return CampaignType::Bundle === $campaign->getType();
    }

    public function evaluate(Cart $cart, Campaign $campaign): ?CampaignDiscount
    {
        $value = $campaign->getDiscountValue();
        $bundleItems = $campaign->getProductsByRole(CampaignProductRole::BundleItem);

        if (null === $value || \count($bundleItems) < 2) {
            return null;
        }

        $base = '0.00';
        foreach ($bundleItems as $product) {
            $item = $this->findCartItem($cart, $product);
            if (!$item) {
                return null;
            }
            $base = bcadd($base, $item->getSubtotal(), 2);
        }

        $amount = bccomp($value, $base, 2) > 0 ? $base : $value;
        $names = implode(' + ', array_map(static fn ($p) => $p->getName(), $bundleItems));

        return new CampaignDiscount(
            campaign: $campaign,
            amount: $amount,
            description: sprintf('%s: bundle %s (−%s lei)', $campaign->getName(), $names, $amount),
        );
    }
}
