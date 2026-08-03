<?php

namespace App\Service\Campaign\Strategy;

use App\Dto\CampaignDiscount;
use App\Entity\Campaign;
use App\Entity\Cart;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.campaign_strategy')]
interface CampaignStrategyInterface
{
    public function supports(Campaign $campaign): bool;

    /**
     * Calculează reducerea produsă de această campanie asupra coșului,
     * independent de alte campanii (motorul le însumează pe toate față
     * de subtotalul original — vezi App\Service\CampaignEngine).
     *
     * @return CampaignDiscount|null null dacă regula nu se aplică (produs
     *                                lipsă din coș, prag neatins etc.)
     */
    public function evaluate(Cart $cart, Campaign $campaign): ?CampaignDiscount;
}
