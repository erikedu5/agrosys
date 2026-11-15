<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchField from '@/Components/SearchField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import InputError from '@/Components/InputError.vue';
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    empresas: Object,
    filters: Object,
    plans: Array,
});

const q = ref(props.filters?.q ?? '');
const selectedEmpresa = ref(null);

const form = useForm({
    plan_code: null,
    plan_cycle: 'monthly',
    manual_subscription_starts_at: '',
    manual_subscription_ends_at: '',
    manual_subscription_blocked: false,
    clear_manual_dates: false,
});

watch(q, (value) => {
    router.get(
        route('empresa.subscriptions.index'),
        { q: value },
        { preserveState: true, replace: true }
    );
});

const selectEmpresa = (empresa) => {
    selectedEmpresa.value = empresa;
    form.plan_code = empresa.plan_code ?? null;
    form.plan_cycle = empresa.plan_cycle ?? 'monthly';
    form.manual_subscription_starts_at = empresa.manual_subscription_starts_at ?? '';
    form.manual_subscription_ends_at = empresa.manual_subscription_ends_at ?? '';
    form.manual_subscription_blocked = Boolean(empresa.manual_subscription_blocked);
    form.clear_manual_dates = false;
    form.clearErrors();
};

const save = () => {
    if (!selectedEmpresa.value) {
        return;
    }

    form.put(route('empresa.subscriptions.update', selectedEmpresa.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.clearErrors();
            form.clear_manual_dates = false;
            router.reload({ only: ['empresas'] });
        },
    });
};

const selectedEmpresaTitle = computed(() => {
    if (!selectedEmpresa.value) {
        return 'Selecciona una empresa';
    }

    return `Configurar: ${selectedEmpresa.value.nombre}`;
});

const selectedPlan = computed(() => {
    if (!form.plan_code) return null;
    return (props.plans || []).find((plan) => plan.code === form.plan_code) || null;
});

watch(
    () => form.plan_code,
    (value) => {
        if (!value) {
            form.plan_cycle = 'monthly';
            return;
        }

        if (form.plan_cycle === 'yearly' && !selectedPlan.value?.has_yearly) {
            form.plan_cycle = 'monthly';
        }
    }
);

const displayPlan = (empresa) => {
    if (!empresa.plan_code) {
        return 'Legacy / sin plan';
    }

    const cycle = empresa.plan_cycle === 'yearly' ? 'anual' : 'mensual';
    return `${empresa.plan_name || empresa.plan_code} (${cycle})`;
};

const formatDate = (value) => {
    if (!value) return 'N/A';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return value;
    return parsed.toLocaleString();
};
</script>

<template>
    <AppLayout title="Panel de Suscripciones">
        <template #header>
            <PageHeader title="Suscripciones" subtitle="Control manual para empresas con pagos fuera de Stripe" :back="route('empresa.index')" />
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar empresa" />

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_400px] xl:items-start">
                <div class="flex min-w-0 flex-col gap-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <RecordCard v-for="empresa in empresas.data" :key="empresa.id" :eyebrow="`ID ${empresa.id}`" :title="empresa.nombre"
                            :subtitle="displayPlan(empresa)" :selected="selectedEmpresa?.id === empresa.id"
                            :fields="[
                                { label: 'Inicio de suscripción', value: formatDate(empresa.manual_subscription_starts_at) },
                                { label: 'Vencimiento', value: formatDate(empresa.manual_subscription_ends_at) },
                                { label: 'Estado', value: empresa.status, wide: true },
                            ]">
                            <template #badge>
                                <span class="ui-badge" :class="empresa.can_access ? 'ui-badge-green' : 'ui-badge-red'">{{ empresa.can_access ? 'Habilitada' : 'Bloqueada' }}</span>
                            </template>
                            <template #actions>
                                <button type="button" class="ui-btn flex-1" @click="selectEmpresa(empresa)">Configurar</button>
                            </template>
                        </RecordCard>
                    </div>

                    <EmptyState v-if="!empresas.data?.length" title="No hay empresas" />
                    <PaginationBar :paginator="empresas" />
                </div>

                <aside class="ui-card flex flex-col gap-4 p-5 xl:sticky xl:top-24">
                    <div>
                        <h2 class="ui-section-title">{{ selectedEmpresaTitle }}</h2>
                        <p class="ui-muted">Define vigencia manual cuando la empresa ya pagó fuera de Stripe.</p>
                    </div>

                    <p v-if="!selectedEmpresa" class="rounded-xl border-2 border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-600 dark:border-gray-600 dark:text-gray-400">
                        Elige “Configurar” en una empresa para editar su vigencia.
                    </p>

                    <form v-else class="flex flex-col gap-4" @submit.prevent="save">
                        <div class="ui-field">
                            <label class="ui-label">Plan</label>
                            <select class="ui-input" v-model="form.plan_code">
                                <option :value="null">Sin plan (legacy)</option>
                                <option v-for="planOption in plans" :key="planOption.code" :value="planOption.code">{{ planOption.name }}</option>
                            </select>
                            <InputError :message="form.errors.plan_code" />
                        </div>

                        <div class="ui-field">
                            <label class="ui-label">Ciclo</label>
                            <select class="ui-input" v-model="form.plan_cycle" :disabled="!form.plan_code">
                                <option value="monthly">Mensual</option>
                                <option value="yearly" :disabled="!selectedPlan?.has_yearly">Anual</option>
                            </select>
                            <InputError :message="form.errors.plan_cycle" />
                        </div>

                        <div class="ui-field">
                            <label class="ui-label">Fecha inicio</label>
                            <input type="datetime-local" class="ui-input" v-model="form.manual_subscription_starts_at" :disabled="form.clear_manual_dates">
                            <InputError :message="form.errors.manual_subscription_starts_at" />
                        </div>

                        <div class="ui-field">
                            <label class="ui-label">Fecha vencimiento</label>
                            <input type="datetime-local" class="ui-input" v-model="form.manual_subscription_ends_at" :disabled="form.clear_manual_dates">
                            <InputError :message="form.errors.manual_subscription_ends_at" />
                        </div>

                        <label class="ui-check">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" v-model="form.clear_manual_dates">
                            Limpiar fechas manuales
                        </label>

                        <label class="ui-check">
                            <input type="checkbox" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500" v-model="form.manual_subscription_blocked">
                            Bloquear empresa manualmente
                        </label>
                        <InputError :message="form.errors.manual_subscription_blocked" />

                        <button type="submit" class="ui-btn ui-btn-primary ui-btn-lg w-full" :disabled="form.processing">Guardar configuración</button>
                    </form>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
