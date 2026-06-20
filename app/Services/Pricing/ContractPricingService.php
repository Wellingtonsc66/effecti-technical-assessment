<?php

namespace App\Services\Pricing;

use App\Models\Contract;

class ContractPricingService
{
    /** @var list<ContractPricingRule> */
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            new QuantityDiscountPricingRule,
        ];
    }

    public function calculate(Contract $contract): array
    {
        $contract->loadMissing(['items.service', 'client']);

        $subtotalCents = $contract->items->sum(
            fn ($item): int => $item->quantity * self::toCents($item->unit_value),
        );

        $adjustments = collect($this->rules)
            ->map(fn (ContractPricingRule $rule): ?PricingAdjustment => $rule->apply($contract, $subtotalCents))
            ->filter()
            ->values();

        $totalCents = $subtotalCents + $adjustments->sum(fn (PricingAdjustment $adjustment): int => $adjustment->amountCents);

        return [
            'subtotal' => self::fromCents($subtotalCents),
            'adjustments' => $adjustments->map(fn (PricingAdjustment $adjustment): array => [
                'label' => $adjustment->label,
                'amount' => $adjustment->amount(),
            ])->all(),
            'total' => self::fromCents(max($totalCents, 0)),
        ];
    }

    public static function toCents(string|float|int $value): int
    {
        return (int) round((float) $value * 100);
    }

    public static function fromCents(int $value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
}
