<?php

namespace App\Services\Pricing;

readonly class PricingAdjustment
{
    public function __construct(
        public string $label,
        public int $amountCents,
    ) {}

    public function amount(): string
    {
        return number_format($this->amountCents / 100, 2, '.', '');
    }
}
