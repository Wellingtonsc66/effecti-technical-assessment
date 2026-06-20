<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractItemRequest;
use App\Http\Requests\ContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Services\ContractManagementService;
use Illuminate\Http\JsonResponse;

class ContractController extends Controller
{
    public function __construct(
        private readonly ContractManagementService $contracts,
    ) {}

    public function index(): JsonResponse
    {
        $contracts = Contract::query()
            ->with(['client', 'items.service', 'changeLogs'])
            ->latest()
            ->paginate(20);

        return ContractResource::collection($contracts)->response();
    }

    public function store(ContractRequest $request): JsonResponse
    {
        $contract = $this->contracts->create($request->validated());

        return (new ContractResource($contract->refresh()))->response()->setStatusCode(201);
    }

    public function show(Contract $contract): JsonResponse
    {
        return (new ContractResource($contract))->response();
    }

    public function update(ContractRequest $request, Contract $contract): JsonResponse
    {
        $contract = $this->contracts->update($contract, $request->validated());

        return (new ContractResource($contract->refresh()))->response();
    }

    public function destroy(Contract $contract): JsonResponse
    {
        $contract->delete();

        return response()->json(status: 204);
    }

    public function addItem(ContractItemRequest $request, Contract $contract): JsonResponse
    {
        $this->contracts->addItem($contract, $request->validated());

        return (new ContractResource($contract->refresh()))->response()->setStatusCode(201);
    }

    public function removeItem(Contract $contract, ContractItem $contractItem): JsonResponse
    {
        $this->contracts->removeItem($contract, $contractItem);

        return (new ContractResource($contract->refresh()))->response();
    }
}
