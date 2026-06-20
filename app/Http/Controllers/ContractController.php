<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractItemRequest;
use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\ContractChange;
use App\Models\ContractItem;
use App\Services\ContractManagementService;
use App\Services\Pricing\ContractPricingService;
use Illuminate\Http\JsonResponse;

class ContractController extends Controller
{
    public function __construct(
        private readonly ContractManagementService $contracts,
        private readonly ContractPricingService $pricing,
    ) {}

    public function index(): JsonResponse
    {
        $contracts = Contract::query()
            ->with(['client', 'items.service', 'changeLogs'])
            ->latest()
            ->paginate(20)
            ->through(fn (Contract $contract): array => $this->payload($contract));

        return response()->json(['data' => $contracts]);
    }

    public function store(ContractRequest $request): JsonResponse
    {
        $contract = $this->contracts->create($request->validated());

        return response()->json(['data' => $this->payload($contract->refresh())], 201);
    }

    public function show(Contract $contract): JsonResponse
    {
        return response()->json(['data' => $this->payload($contract)]);
    }

    public function update(ContractRequest $request, Contract $contract): JsonResponse
    {
        $contract = $this->contracts->update($contract, $request->validated());

        return response()->json(['data' => $this->payload($contract->refresh())]);
    }

    public function destroy(Contract $contract): JsonResponse
    {
        $contract->delete();

        return response()->json(status: 204);
    }

    public function addItem(ContractItemRequest $request, Contract $contract): JsonResponse
    {
        $this->contracts->addItem($contract, $request->validated());

        return response()->json(['data' => $this->payload($contract->refresh())], 201);
    }

    public function removeItem(Contract $contract, ContractItem $contractItem): JsonResponse
    {
        $this->contracts->removeItem($contract, $contractItem);

        return response()->json(['data' => $this->payload($contract->refresh())]);
    }

    private function payload(Contract $contract): array
    {
        $contract->loadMissing(['client', 'items.service', 'changeLogs']);

        return [
            'id' => $contract->id,
            'client_id' => $contract->client_id,
            'client' => $contract->client,
            'start_date' => $contract->start_date?->toDateString(),
            'end_date' => $contract->end_date?->toDateString(),
            'status' => $contract->status->value,
            'items' => $contract->items->map(fn (ContractItem $item): array => [
                'id' => $item->id,
                'service_id' => $item->service_id,
                'service' => $item->service,
                'quantity' => $item->quantity,
                'unit_value' => $item->unit_value,
                'line_total' => number_format($item->quantity * (float) $item->unit_value, 2, '.', ''),
            ])->values(),
            'changes' => $contract->changeLogs->map(fn (ContractChange $change): array => [
                'id' => $change->id,
                'description' => $change->description,
                'data' => $change->data,
                'created_at' => $change->created_at?->toISOString(),
            ])->values(),
            'pricing' => $this->pricing->calculate($contract),
        ];
    }
}
