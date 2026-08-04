<?php

namespace App\Enum;

enum CampaignProductRole: string
{
    /** Produsul la care se aplică reducerea (CampaignType::Discount). */
    case Target = 'target';
    /** Produsul care trebuie adăugat manual în coș — declanșează BOGO. */
    case Trigger = 'trigger';
    /** Produsul oferit gratis (BOGO cu produs diferit de declanșator, sau gift_threshold). */
    case Gift = 'gift';
    /** Parte dintr-un bundle. */
    case BundleItem = 'bundle_item';
}
