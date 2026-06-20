<?php

namespace App\Services;

use App\Enums\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractManagementService
{
    public function create(array $data): Contract
    {
        return DB::transaction(function () use ($data): Contract {
            $contract = Contract::query()->create($data);
            $this->registerChange($contract, 'Contrato criado', $data);

            return $contract;
        });
    }

    public function update(Contract $contract, array $data): Contract
    {
        $this->ensureCanEdit($contract);

        return DB::transaction(function () use ($contract, $data): Contract {
            $contract->update($data);
            $this->registerChange($contract, 'Contrato atualizado', $data);

            return $contract;
        });
    }

    public function addItem(Contract $contract, array $data): ContractItem
    {
        $this->ensureCanEdit($contract);

        return DB::transaction(function () use ($contract, $data): ContractItem {
            $service = Service::query()->findOrFail($data['service_id']);

            $item = $contract->items()->create([
                'service_id' => $service->id,
                'quantity' => $data['quantity'],
                'unit_value' => $data['unit_value'] ?? $service->monthly_base_value,
            ]);

            $this->registerChange($contract, 'Serviço adicionado ao contrato', $item->only(['service_id', 'quantity', 'unit_value']));

            return $item;
        });
    }

    public function removeItem(Contract $contract, ContractItem $item): void
    {
        $this->ensureCanEdit($contract);

        if ($item->contract_id !== $contract->id) {
            abort(404);
        }

        DB::transaction(function () use ($contract, $item): void {
            $payload = $item->only(['service_id', 'quantity', 'unit_value']);
            $item->delete();
            $this->registerChange($contract, 'Serviço removido do contrato', $payload);
        });
    }

    public function ensureCanEdit(Contract $contract): void
    {
        if ($contract->status === ContractStatusEnum::Canceled) {
            throw ValidationException::withMessages([
                'contract' => 'Contratos cancelados não podem ser editados.',
            ]);
        }
    }

    private function registerChange(Contract $contract, string $description, array $data): void
    {
        $contract->changeLogs()->create([
            'description' => $description,
            'data' => $data,
        ]);
    }
}
