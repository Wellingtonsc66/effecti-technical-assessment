<?php

namespace App\Services\Pricing;

use App\Models\Contract;

class QuantityDiscountPricingRule implements ContractPricingRule
{
    public function apply(Contract $contract, int $subtotalCents): ?PricingAdjustment
    {
        $discountCents = $contract->items->sum(function ($item): int {
            if ($item->quantity < 5) {
                return 0;
            }

            return (int) round(($item->quantity * ContractPricingService::toCents($item->unit_value)) * 0.10);
        });

        if ($discountCents <= 0) {
            return null;
        }

        return new PricingAdjustment('Desconto por quantidade', -$discountCents);
    }
}
