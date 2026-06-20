<script setup>
import { computed, ref } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import axios from 'axios';

const search = ref('');
const loading = ref(false);
const apiStatus = ref(null);

const trimmedSearch = computed(() => search.value.trim());

async function checkApi() {
    loading.value = true;
    apiStatus.value = null;

    try {
        await axios.get('/');

        apiStatus.value = {
            severity: 'success',
            summary: 'Axios configurado e respondendo pelo Laravel.',
        };
    } catch (error) {
        apiStatus.value = {
            severity: 'error',
            summary: error.response?.status
                ? `A requisição retornou status ${error.response.status}.`
                : 'Não foi possível concluir a requisição.',
        };
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="min-h-screen bg-slate-50 px-4 py-8 text-slate-950 dark:bg-slate-950 dark:text-slate-50 sm:px-6 lg:px-8">
        <section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
            <div class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Effecti Technical Assessment</p>
                        <h1 class="mt-1 text-3xl font-semibold tracking-normal text-slate-950 dark:text-white">Base frontend pronta</h1>
                    </div>

                    <Tag value="Vue + PrimeVue + Axios" severity="success" />
                </div>

                <p class="max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-300">
                    Estrutura inicial configurada para evoluir o front da avaliação técnica com Vue 3, componentes PrimeVue e chamadas HTTP via Axios.
                </p>
            </div>

            <Card>
                <template #title>PrimeVue básico</template>
                <template #subtitle>Componentes renderizados pelo Vue dentro do Laravel.</template>

                <template #content>
                    <div class="flex flex-col gap-5">
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Busca de exemplo</span>
                            <InputText v-model="search" placeholder="Digite algo para testar o binding" />
                        </label>

                        <p class="min-h-6 text-sm text-slate-600 dark:text-slate-300">
                            <span v-if="trimmedSearch">Valor digitado: {{ trimmedSearch }}</span>
                        </p>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button label="Testar Axios" :loading="loading" @click="checkApi" />
                            <Message v-if="apiStatus" :severity="apiStatus.severity" :closable="false">
                                {{ apiStatus.summary }}
                            </Message>
                        </div>
                    </div>
                </template>
            </Card>
        </section>
    </main>
</template>
