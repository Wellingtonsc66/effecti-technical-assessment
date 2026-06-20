<?php

namespace App\Http\Resources;

use App\Models\ContractChange;
use App\Models\ContractItem;
use App\Services\Pricing\ContractPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['client', 'items.service', 'changeLogs']);

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client' => $this->client,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status->value,
            'items' => $this->items->map(fn (ContractItem $item): array => [
                'id' => $item->id,
                'service_id' => $item->service_id,
                'service' => $item->service,
                'quantity' => $item->quantity,
                'unit_value' => $item->unit_value,
                'line_total' => number_format($item->quantity * (float) $item->unit_value, 2, '.', ''),
            ])->values(),
            'changes' => $this->changeLogs->map(fn (ContractChange $change): array => [
                'id' => $change->id,
                'description' => $change->description,
                'data' => $change->data,
                'created_at' => $change->created_at?->toISOString(),
            ])->values(),
            'pricing' => app(ContractPricingService::class)->calculate($this->resource),
        ];
    }
}