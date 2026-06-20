<?php

namespace App\Services\Pricing;

use App\Models\Contract;

interface ContractPricingRule
{
    public function apply(Contract $contract, int $subtotalCents): ?PricingAdjustment;
}
