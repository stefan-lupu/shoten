<?php

namespace App\Dto;

/**
 * @param CampaignDiscount[] $discounts
 */
final readonly class CampaignResult
{
    public function __construct(
        public string $subtotal,
        public string $total,
        public array $discounts,
        public ?string $couponError = null,
    ) {
    }

    public function getTotalDiscount(): string
    {
        return bcsub($this->subtotal, $this->total, 2);
    }
}
