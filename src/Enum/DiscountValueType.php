<?php

namespace App\Enum;

/**
 * Cum se interpretează `Campaign::$discountValue` pentru tipul `discount` —
 * separat explicit de `CampaignType` ca admin-ul să aleagă clar, dintr-un
 * select dedicat, dacă valoarea introdusă e procent sau sumă fixă în lei.
 */
enum DiscountValueType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
