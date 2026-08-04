<?php

namespace App\Enum;

enum CampaignType: string
{
    /** Reducere pe produse țintă (sau tot coșul, dacă nu are ținte) — procent sau sumă fixă, vezi DiscountValueType. */
    case Discount = 'discount';
    case Coupon = 'coupon';
    case Bogo = 'bogo';
    case GiftThreshold = 'gift_threshold';
    case Bundle = 'bundle';
}
