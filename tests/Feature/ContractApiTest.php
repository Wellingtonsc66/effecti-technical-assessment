<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_document_must_be_valid(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'Cliente inválido',
            'document' => '11111111111',
            'email' => 'cliente@example.com',
            'status' => 'active',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document');
    }

    public function test_client_email_must_have_complete_domain(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'Cliente inválido',
            'document' => '52998224725',
            'email' => 'cliente@gmail',
            'status' => 'active',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_contract_end_date_validation_message_is_translated(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Teste',
            'document' => '52998224725',
            'email' => 'cliente@example.com',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/contracts', [
            'client_id' => $client->id,
            'start_date' => '2020-05-20',
            'end_date' => '2020-01-01',
            'status' => 'active',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.end_date.0', 'A data de término deve ser posterior ou igual à data de início.');
    }

    public function test_contract_client_validation_message_is_translated(): void
    {
        $response = $this->postJson('/api/contracts', [
            'client_id' => 999,
            'start_date' => '2020-05-20',
            'status' => 'active',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.client_id.0', 'O cliente selecionado é inválido.');
    }

    public function test_contract_total_is_calculated_from_current_items_and_quantity_discount(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Teste',
            'document' => '52998224725',
            'email' => 'cliente@example.com',
            'status' => 'active',
        ]);

        $serviceA = Service::query()->create([
            'name' => 'Serviço A',
            'monthly_base_value' => 100,
        ]);

        $serviceB = Service::query()->create([
            'name' => 'Serviço B',
            'monthly_base_value' => 200,
        ]);

        $contractId = $this->postJson('/api/contracts', [
            'client_id' => $client->id,
            'start_date' => '2026-06-20',
            'status' => 'active',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/contracts/{$contractId}/items", [
            'service_id' => $serviceA->id,
            'quantity' => 2,
            'unit_value' => 100,
        ])->assertCreated();

        $response = $this->postJson("/api/contracts/{$contractId}/items", [
            'service_id' => $serviceB->id,
            'quantity' => 5,
            'unit_value' => 200,
        ])->assertCreated();

        $response
            ->assertJsonPath('data.pricing.subtotal', '1200.00')
            ->assertJsonPath('data.pricing.adjustments.0.label', 'Desconto por quantidade')
            ->assertJsonPath('data.pricing.adjustments.0.amount', '-100.00')
            ->assertJsonPath('data.pricing.total', '1100.00');
    }

    public function test_canceled_contract_cannot_be_edited(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Teste',
            'document' => '52998224725',
            'email' => 'cliente@example.com',
            'status' => 'active',
        ]);

        $contract = Contract::query()->create([
            'client_id' => $client->id,
            'start_date' => '2026-06-20',
            'status' => 'canceled',
        ]);

        $service = Service::query()->create([
            'name' => 'Serviço A',
            'monthly_base_value' => 100,
        ]);

        $this->postJson("/api/contracts/{$contract->id}/items", [
            'service_id' => $service->id,
            'quantity' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('contract');
    }
}
