<?php

namespace App\Service;

use App\Dto\CampaignResult;
use App\Entity\Campaign;
use App\Entity\Cart;
use App\Enum\CampaignType;
use App\Repository\CampaignRepository;
use App\Repository\ShippingSettingsRepository;
use App\Service\Campaign\Strategy\CampaignStrategyInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Motorul de campanii. Fiecare campanie activă (mai puțin cupoanele, care
 * necesită un cod introdus explicit) e evaluată INDEPENDENT față de
 * subtotalul original al coșului — reducerile nu se compun una peste alta
 * (nu se aplică % dintr-un total deja redus). Motivul: predictibilitate —
 * clientul poate verifica fiecare reducere separat, iar rezultatul nu
 * depinde de ordinea în care campaniile au fost create.
 *
 * Ordinea de EVALUARE (relevantă doar pentru ordinea afișării, nu pentru
 * matematică, care e independentă): reduceri (procent/fix) → BOGO → cadou la
 * prag → bundle → cupon (cuponul e mereu ultimul, conform cerinței
 * „reduceri înainte de cupon fix").
 *
 * Suma tuturor reducerilor e scăzută o singură dată din subtotal, iar
 * totalul final e limitat la minim 0.
 */
final class CampaignEngine
{
    private const array AUTOMATIC_TYPE_ORDER = [
        CampaignType::Discount,
        CampaignType::Bogo,
        CampaignType::GiftThreshold,
        CampaignType::Bundle,
    ];

    /**
     * @param iterable<CampaignStrategyInterface> $strategies
     */
    public function __construct(
        #[AutowireIterator('app.campaign_strategy')]
        private readonly iterable $strategies,
        private readonly CampaignRepository $campaignRepository,
        private readonly ShippingSettingsRepository $shippingSettingsRepository,
    ) {
    }

    public function applyCampaigns(Cart $cart, ?string $couponCode = null): CampaignResult
    {
        $now = new \DateTimeImmutable();
        $active = array_filter(
            $this->campaignRepository->findActive(),
            static fn (Campaign $c) => $c->isWithinDateWindow($now),
        );

        $subtotal = '0.00';
        foreach ($cart->getItems() as $item) {
            $subtotal = bcadd($subtotal, $item->getSubtotal(), 2);
        }

        $campaignsToEvaluate = [];
        foreach (self::AUTOMATIC_TYPE_ORDER as $type) {
            foreach ($active as $campaign) {
                if ($campaign->getType() === $type) {
                    $campaignsToEvaluate[] = $campaign;
                }
            }
        }

        $couponError = null;
        $couponCode = $couponCode ? trim($couponCode) : null;
        if ($couponCode) {
            $couponCampaign = null;
            foreach ($active as $campaign) {
                if (CampaignType::Coupon === $campaign->getType()
                    && $campaign->getCouponCode()
                    && 0 === strcasecmp($campaign->getCouponCode(), $couponCode)
                ) {
                    $couponCampaign = $campaign;
                    break;
                }
            }

            if (!$couponCampaign) {
                // Poate exista dezactivat/în afara ferestrei de date — verificăm separat ca să dăm un mesaj clar.
                $inactiveMatch = $this->campaignRepository->findOneBy(['couponCode' => $couponCode, 'type' => CampaignType::Coupon]);
                $couponError = match (true) {
                    null === $inactiveMatch => 'Cod promoțional invalid.',
                    !$inactiveMatch->isActive() => 'Acest cod promoțional nu mai este activ.',
                    !$inactiveMatch->isWithinDateWindow($now) => 'Acest cod promoțional a expirat sau nu este încă valabil.',
                    default => 'Cod promoțional invalid.',
                };
            } elseif (!$couponCampaign->hasUsesRemaining()) {
                $couponError = 'Acest cod promoțional a atins limita de utilizări.';
            } else {
                $campaignsToEvaluate[] = $couponCampaign;
            }
        }

        $discounts = [];
        foreach ($campaignsToEvaluate as $campaign) {
            foreach ($this->strategies as $strategy) {
                if ($strategy->supports($campaign)) {
                    $discount = $strategy->evaluate($cart, $campaign);
                    if ($discount) {
                        $discounts[] = $discount;
                    }
                    break;
                }
            }
        }

        $totalDiscount = '0.00';
        foreach ($discounts as $discount) {
            $totalDiscount = bcadd($totalDiscount, $discount->amount, 2);
        }

        $total = bcsub($subtotal, $totalDiscount, 2);
        if (bccomp($total, '0.00', 2) < 0) {
            $total = '0.00';
        }

        $shippingCost = $this->calculateShippingCost($subtotal);
        $total = bcadd($total, $shippingCost, 2);

        return new CampaignResult(
            subtotal: $subtotal,
            shippingCost: $shippingCost,
            total: $total,
            discounts: $discounts,
            couponError: $couponError,
        );
    }

    private function calculateShippingCost(string $subtotal): string
    {
        if (bccomp($subtotal, '0.00', 2) <= 0) {
            return '0.00';
        }

        $settings = $this->shippingSettingsRepository->getSettings();
        $threshold = $settings->getFreeShippingThreshold();
        if (null !== $threshold && bccomp($subtotal, $threshold, 2) >= 0) {
            return '0.00';
        }

        return $settings->getCost();
    }
}
