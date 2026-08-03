<?php

namespace App\Enum;

enum CampaignType: string
{
    case PercentageDiscount = 'percentage_discount';
    case FixedDiscount = 'fixed_discount';
    case Coupon = 'coupon';
    case Bogo = 'bogo';
    case GiftThreshold = 'gift_threshold';
    case Bundle = 'bundle';
}
