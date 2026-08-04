<?php

namespace App\Dto;

use App\Entity\Campaign;
use App\Entity\Product;

/**
 * O reducere aplicată efectiv pe coș — folosită pentru afișare transparentă
 * („−15 lei aplicat: Cod PRIMAVARA20") și pentru a ști ce campanie să
 * incrementăm (usesCount) la plasarea comenzii.
 *
 * `freeGiftProduct`/`freeGiftQuantity` sunt setate doar de campaniile BOGO
 * cu produs cadou diferit de declanșator: produsul nu există ca CartItem
 * real (clientul nu trebuie să-l adauge manual) — apare ca linie
 * informativă în coș/checkout și devine un OrderItem cu preț 0 la
 * finalizarea comenzii (vezi OrderService::placeOrder).
 */
final readonly class CampaignDiscount
{
    public function __construct(
        public Campaign $campaign,
        public string $amount,
        public string $description,
        public ?Product $freeGiftProduct = null,
        public int $freeGiftQuantity = 0,
    ) {
    }
}
