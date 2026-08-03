<?php

namespace App\Enum;

enum CampaignProductRole: string
{
    /** Produsul la care se aplică reducerea (percentage_discount/fixed_discount). */
    case Target = 'target';
    /** Produsul care declanșează BOGO. */
    case Trigger = 'trigger';
    /** Produsul oferit gratis (BOGO sau gift_threshold). */
    case Gift = 'gift';
    /** Parte dintr-un bundle. */
    case BundleItem = 'bundle_item';
}
