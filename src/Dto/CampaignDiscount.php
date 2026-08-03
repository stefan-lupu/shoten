<?php

namespace App\Dto;

use App\Entity\Campaign;

/**
 * O reducere aplicată efectiv pe coș — folosită pentru afișare transparentă
 * („−15 lei aplicat: Cod PRIMAVARA20") și pentru a ști ce campanie să
 * incrementăm (usesCount) la plasarea comenzii.
 */
final readonly class CampaignDiscount
{
    public function __construct(
        public Campaign $campaign,
        public string $amount,
        public string $description,
    ) {
    }
}
