<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import axios from 'axios';

const tabs = [
    { key: 'clients', label: 'Clientes' },
    { key: 'services', label: 'Serviços' },
    { key: 'contracts', label: 'Contratos' },
];

const activeTab = ref('clients');
const clients = ref([]);
const services = ref([]);
const contracts = ref([]);
const loading = ref(false);
const feedback = ref(null);

const clientForm = reactive({ id: null, name: '', document: '', email: '', status: 'active' });
const serviceForm = reactive({ id: null, name: '', monthly_base_value: '' });
const contractForm = reactive({ id: null, client_id: '', start_date: '', end_date: '', status: 'active' });
const itemForm = reactive({ contract_id: '', service_id: '', quantity: 1, unit_value: '' });

const selectedContract = computed(() => contracts.value.find((contract) => contract.id === Number(itemForm.contract_id)) ?? null);
const canEditSelectedContract = computed(() => selectedContract.value?.status !== 'canceled');

onMounted(loadData);

async function loadData() {
    loading.value = true;
    feedback.value = null;

    try {
        const [clientsResponse, servicesResponse, contractsResponse] = await Promise.all([
            axios.get('/api/clients'),
            axios.get('/api/services'),
            axios.get('/api/contracts'),
        ]);

        clients.value = pageItems(clientsResponse);
        services.value = pageItems(servicesResponse);
        contracts.value = pageItems(contractsResponse);

        if (!itemForm.contract_id && contracts.value.length > 0) {
            itemForm.contract_id = contracts.value[0].id;
        }
    } catch (error) {
        showError(error);
    } finally {
        loading.value = false;
    }
}

async function saveClient() {
    await saveEntity('/api/clients', clientForm, resetClientForm, 'Cliente salvo com sucesso.');
}

async function saveService() {
    await saveEntity('/api/services', serviceForm, resetServiceForm, 'Serviço salvo com sucesso.');
}

async function saveContract() {
    await saveEntity('/api/contracts', contractForm, resetContractForm, 'Contrato salvo com sucesso.');
}

async function saveEntity(endpoint, form, resetForm, successMessage) {
    loading.value = true;
    feedback.value = null;

    try {
        const payload = Object.fromEntries(
            Object.entries(form).filter(([key, value]) => key !== 'id' && value !== '' && value !== null),
        );

        if (form.id) {
            await axios.put(`${endpoint}/${form.id}`, payload);
        } else {
            await axios.post(endpoint, payload);
        }

        resetForm();
        await loadData();
        showSuccess(successMessage);
    } catch (error) {
        showError(error);
    } finally {
        loading.value = false;
    }
}

async function deleteEntity(endpoint, id, successMessage) {
    loading.value = true;
    feedback.value = null;

    try {
        await axios.delete(`${endpoint}/${id}`);
        await loadData();
        showSuccess(successMessage);
    } catch (error) {
        showError(error);
    } finally {
        loading.value = false;
    }
}

async function addContractItem() {
    if (!itemForm.contract_id) {
        feedback.value = { severity: 'warn', text: 'Selecione um contrato para adicionar serviços.' };
        return;
    }

    loading.value = true;
    feedback.value = null;

    try {
        const payload = {
            service_id: itemForm.service_id,
            quantity: itemForm.quantity,
        };

        if (itemForm.unit_value !== '') {
            payload.unit_value = itemForm.unit_value;
        }

        await axios.post(`/api/contracts/${itemForm.contract_id}/items`, payload);
        resetItemForm(false);
        await loadData();
        showSuccess('Serviço adicionado ao contrato.');
    } catch (error) {
        showError(error);
    } finally {
        loading.value = false;
    }
}

async function removeContractItem(contractId, itemId) {
    loading.value = true;
    feedback.value = null;

    try {
        await axios.delete(`/api/contracts/${contractId}/items/${itemId}`);
        await loadData();
        showSuccess('Serviço removido do contrato.');
    } catch (error) {
        showError(error);
    } finally {
        loading.value = false;
    }
}

function editClient(client) {
    Object.assign(clientForm, pick(client, ['id', 'name', 'document', 'email', 'status']));
    activeTab.value = 'clients';
}

function editService(service) {
    Object.assign(serviceForm, pick(service, ['id', 'name', 'monthly_base_value']));
    activeTab.value = 'services';
}

function editContract(contract) {
    Object.assign(contractForm, pick(contract, ['id', 'client_id', 'start_date', 'end_date', 'status']));
    activeTab.value = 'contracts';
}

function useBaseValue() {
    const service = services.value.find((item) => item.id === Number(itemForm.service_id));
    itemForm.unit_value = service?.monthly_base_value ?? '';
}

function resetClientForm() {
    Object.assign(clientForm, { id: null, name: '', document: '', email: '', status: 'active' });
}

function resetServiceForm() {
    Object.assign(serviceForm, { id: null, name: '', monthly_base_value: '' });
}

function resetContractForm() {
    Object.assign(contractForm, { id: null, client_id: '', start_date: '', end_date: '', status: 'active' });
}

function resetItemForm(clearContract = true) {
    Object.assign(itemForm, {
        contract_id: clearContract ? '' : itemForm.contract_id,
        service_id: '',
        quantity: 1,
        unit_value: '',
    });
}

function pageItems(response) {
    return response.data.data.data ?? response.data.data ?? [];
}

function pick(source, keys) {
    return keys.reduce((payload, key) => ({ ...payload, [key]: source[key] ?? '' }), {});
}

function showSuccess(text) {
    feedback.value = { severity: 'success', text };
}

function showError(error) {
    const errors = error.response?.data?.errors;
    const firstValidationError = errors ? Object.values(errors).flat()[0] : null;

    feedback.value = {
        severity: 'error',
        text: firstValidationError ?? error.response?.data?.message ?? 'Não foi possível concluir a operação.',
    };
}

function statusLabel(status) {
    return {
        active: 'Ativo',
        inactive: 'Inativo',
        canceled: 'Cancelado',
    }[status] ?? status;
}

function statusSeverity(status) {
    return status === 'active' ? 'success' : 'danger';
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value ?? 0));
}
</script>

<template>
    <main class="min-h-screen bg-slate-50 px-4 py-6 text-slate-950 dark:bg-slate-950 dark:text-slate-50 sm:px-6 lg:px-8">
        <section class="mx-auto flex w-full max-w-7xl flex-col gap-5">
            <header class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Avaliação Técnica Effecti</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-normal text-slate-950 dark:text-white">ERP de contratos e serviços</h1>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Button label="Atualizar" size="small" :loading="loading" @click="loadData" />
                    <Tag value="Vue + PrimeVue + API REST" severity="success" />
                </div>
            </header>

            <Message v-if="feedback" :severity="feedback.severity" :closable="false">
                {{ feedback.text }}
            </Message>

            <nav class="flex flex-wrap gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-md border px-4 py-2 text-sm font-medium transition dark:border-slate-800"
                    :class="activeTab === tab.key ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-500 dark:bg-slate-900 dark:text-slate-200'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </nav>

            <section v-show="activeTab === 'clients'" class="grid gap-5 lg:grid-cols-[360px_1fr]">
                <Card>
                    <template #title>{{ clientForm.id ? 'Editar cliente' : 'Novo cliente' }}</template>
                    <template #content>
                        <form class="flex flex-col gap-4" @submit.prevent="saveClient">
                            <label class="field-label">
                                Nome
                                <InputText v-model="clientForm.name" required />
                            </label>

                            <label class="field-label">
                                CPF ou CNPJ
                                <InputText v-model="clientForm.document" required />
                            </label>

                            <label class="field-label">
                                Email
                                <InputText v-model="clientForm.email" type="email" required />
                            </label>

                            <label class="field-label">
                                Status
                                <select v-model="clientForm.status" class="form-select">
                                    <option value="active">Ativo</option>
                                    <option value="inactive">Inativo</option>
                                </select>
                            </label>

                            <div class="flex gap-2">
                                <Button type="submit" label="Salvar" :loading="loading" />
                                <Button type="button" label="Limpar" severity="secondary" outlined @click="resetClientForm" />
                            </div>
                        </form>
                    </template>
                </Card>

                <Card>
                    <template #title>Clientes cadastrados</template>
                    <template #content>
                        <div class="overflow-x-auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Documento</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="client in clients" :key="client.id">
                                        <td>{{ client.name }}</td>
                                        <td>{{ client.document }}</td>
                                        <td>{{ client.email }}</td>
                                        <td><Tag :value="statusLabel(client.status)" :severity="statusSeverity(client.status)" /></td>
                                        <td class="actions-cell">
                                            <Button label="Editar" size="small" severity="secondary" outlined @click="editClient(client)" />
                                            <Button label="Excluir" size="small" severity="danger" outlined @click="deleteEntity('/api/clients', client.id, 'Cliente excluído.')" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </Card>
            </section>

            <section v-show="activeTab === 'services'" class="grid gap-5 lg:grid-cols-[360px_1fr]">
                <Card>
                    <template #title>{{ serviceForm.id ? 'Editar serviço' : 'Novo serviço' }}</template>
                    <template #content>
                        <form class="flex flex-col gap-4" @submit.prevent="saveService">
                            <label class="field-label">
                                Nome
                                <InputText v-model="serviceForm.name" required />
                            </label>

                            <label class="field-label">
                                Valor base mensal
                                <InputText v-model="serviceForm.monthly_base_value" inputmode="decimal" required />
                            </label>

                            <div class="flex gap-2">
                                <Button type="submit" label="Salvar" :loading="loading" />
                                <Button type="button" label="Limpar" severity="secondary" outlined @click="resetServiceForm" />
                            </div>
                        </form>
                    </template>
                </Card>

                <Card>
                    <template #title>Serviços cadastrados</template>
                    <template #content>
                        <div class="overflow-x-auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Valor base mensal</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="service in services" :key="service.id">
                                        <td>{{ service.name }}</td>
                                        <td>{{ formatCurrency(service.monthly_base_value) }}</td>
                                        <td class="actions-cell">
                                            <Button label="Editar" size="small" severity="secondary" outlined @click="editService(service)" />
                                            <Button label="Excluir" size="small" severity="danger" outlined @click="deleteEntity('/api/services', service.id, 'Serviço excluído.')" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </Card>
            </section>

            <section v-show="activeTab === 'contracts'" class="grid gap-5 xl:grid-cols-[360px_1fr]">
                <div class="flex flex-col gap-5">
                    <Card>
                        <template #title>{{ contractForm.id ? 'Editar contrato' : 'Novo contrato' }}</template>
                        <template #content>
                            <form class="flex flex-col gap-4" @submit.prevent="saveContract">
                                <label class="field-label">
                                    Cliente
                                    <select v-model="contractForm.client_id" class="form-select" required>
                                        <option value="">Selecione</option>
                                        <option v-for="client in clients" :key="client.id" :value="client.id">
                                            {{ client.name }}
                                        </option>
                                    </select>
                                </label>

                                <label class="field-label">
                                    Data de início
                                    <InputText v-model="contractForm.start_date" type="date" required />
                                </label>

                                <label class="field-label">
                                    Data de término
                                    <InputText v-model="contractForm.end_date" type="date" />
                                </label>

                                <label class="field-label">
                                    Status
                                    <select v-model="contractForm.status" class="form-select">
                                        <option value="active">Ativo</option>
                                        <option value="canceled">Cancelado</option>
                                    </select>
                                </label>

                                <div class="flex gap-2">
                                    <Button type="submit" label="Salvar" :loading="loading" />
                                    <Button type="button" label="Limpar" severity="secondary" outlined @click="resetContractForm" />
                                </div>
                            </form>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Adicionar serviço</template>
                        <template #content>
                            <form class="flex flex-col gap-4" @submit.prevent="addContractItem">
                                <label class="field-label">
                                    Contrato
                                    <select v-model="itemForm.contract_id" class="form-select" required>
                                        <option value="">Selecione</option>
                                        <option v-for="contract in contracts" :key="contract.id" :value="contract.id">
                                            #{{ contract.id }} - {{ contract.client?.name }}
                                        </option>
                                    </select>
                                </label>

                                <Message v-if="selectedContract && !canEditSelectedContract" severity="warn" :closable="false">
                                    Contratos cancelados não podem receber alterações.
                                </Message>

                                <label class="field-label">
                                    Serviço
                                    <select v-model="itemForm.service_id" class="form-select" :disabled="!canEditSelectedContract" required @change="useBaseValue">
                                        <option value="">Selecione</option>
                                        <option v-for="service in services" :key="service.id" :value="service.id">
                                            {{ service.name }} - {{ formatCurrency(service.monthly_base_value) }}
                                        </option>
                                    </select>
                                </label>

                                <label class="field-label">
                                    Quantidade
                                    <InputText v-model="itemForm.quantity" type="number" min="1" :disabled="!canEditSelectedContract" required />
                                </label>

                                <label class="field-label">
                                    Valor unitário
                                    <InputText v-model="itemForm.unit_value" inputmode="decimal" :disabled="!canEditSelectedContract" />
                                </label>

                                <Button type="submit" label="Adicionar" :disabled="!canEditSelectedContract" :loading="loading" />
                            </form>
                        </template>
                    </Card>
                </div>

                <div class="flex flex-col gap-5">
                    <Card v-for="contract in contracts" :key="contract.id">
                        <template #title>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <span>Contrato #{{ contract.id }} - {{ contract.client?.name }}</span>
                                <div class="flex flex-wrap gap-2">
                                    <Tag :value="statusLabel(contract.status)" :severity="statusSeverity(contract.status)" />
                                    <Tag :value="formatCurrency(contract.pricing.total)" severity="info" />
                                </div>
                            </div>
                        </template>

                        <template #subtitle>
                            Início: {{ contract.start_date }} <span v-if="contract.end_date">| Término: {{ contract.end_date }}</span>
                        </template>

                        <template #content>
                            <div class="flex flex-col gap-4">
                                <div class="overflow-x-auto">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Serviço</th>
                                                <th>Qtd.</th>
                                                <th>Valor unitário</th>
                                                <th>Total do item</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in contract.items" :key="item.id">
                                                <td>{{ item.service?.name }}</td>
                                                <td>{{ item.quantity }}</td>
                                                <td>{{ formatCurrency(item.unit_value) }}</td>
                                                <td>{{ formatCurrency(item.line_total) }}</td>
                                                <td>
                                                    <Button label="Remover" size="small" severity="danger" outlined :disabled="contract.status === 'canceled'" @click="removeContractItem(contract.id, item.id)" />
                                                </td>
                                            </tr>
                                            <tr v-if="contract.items.length === 0">
                                                <td colspan="5">Nenhum serviço vinculado.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="grid gap-3 rounded-lg bg-slate-100 p-4 text-sm dark:bg-slate-800 sm:grid-cols-3">
                                    <span>Subtotal: <strong>{{ formatCurrency(contract.pricing.subtotal) }}</strong></span>
                                    <span v-for="adjustment in contract.pricing.adjustments" :key="adjustment.label">
                                        {{ adjustment.label }}: <strong>{{ formatCurrency(adjustment.amount) }}</strong>
                                    </span>
                                    <span>Total mensal: <strong>{{ formatCurrency(contract.pricing.total) }}</strong></span>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button label="Editar" size="small" severity="secondary" outlined :disabled="contract.status === 'canceled'" @click="editContract(contract)" />
                                    <Button label="Excluir" size="small" severity="danger" outlined @click="deleteEntity('/api/contracts', contract.id, 'Contrato excluído.')" />
                                </div>

                                <details v-if="contract.changes.length" class="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-700">
                                    <summary class="cursor-pointer font-medium">Histórico de alterações</summary>
                                    <ul class="mt-3 flex flex-col gap-2 text-slate-600 dark:text-slate-300">
                                        <li v-for="change in contract.changes" :key="change.id">
                                            {{ change.description }}
                                        </li>
                                    </ul>
                                </details>
                            </div>
                        </template>
                    </Card>
                </div>
            </section>
        </section>
    </main>
</template>

<style scoped>
.field-label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.form-select {
    width: 100%;
    border-radius: 0.375rem;
    border: 1px solid rgb(203 213 225);
    background: white;
    padding: 0.625rem 0.75rem;
    color: rgb(15 23 42);
}

.data-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th,
.data-table td {
    border-bottom: 1px solid rgb(226 232 240);
    padding: 0.75rem;
    text-align: left;
    vertical-align: middle;
}

.data-table th {
    color: rgb(71 85 105);
    font-weight: 600;
}

.actions-cell {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

@media (prefers-color-scheme: dark) {
    .form-select {
        border-color: rgb(51 65 85);
        background: rgb(15 23 42);
        color: rgb(248 250 252);
    }

    .data-table th,
    .data-table td {
        border-color: rgb(51 65 85);
    }

    .data-table th {
        color: rgb(203 213 225);
    }
}
</style>
