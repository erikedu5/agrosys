<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
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
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Panel de Suscripciones (SuperAdmin)
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Control manual para empresas con pagos fuera de Stripe.
                    </p>
                </div>
                <Link
                    :href="route('empresa.index')"
                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700"
                >
                    Volver a empresas
                </Link>
            </div>
            <br>
            <input
                type="text"
                class="form-input rounded-md shadow-sm w-full"
                v-model="q"
                placeholder="Buscar empresa..."
            >
        </template>

        <hr class="my-6">

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-lg shadow p-4 overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-3 [&>th]:py-3">
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Inicio suscripción</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                            <th>Acceso</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="empresa in empresas.data" :key="empresa.id" class="border-b">
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-900">{{ empresa.nombre }}</div>
                                <div class="text-xs text-gray-500">ID: {{ empresa.id }}</div>
                            </td>
                            <td class="px-3 py-2">
                                {{ displayPlan(empresa) }}
                            </td>
                            <td class="px-3 py-2">{{ formatDate(empresa.manual_subscription_starts_at) }}</td>
                            <td class="px-3 py-2">{{ formatDate(empresa.manual_subscription_ends_at) }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-700">
                                    {{ empresa.status }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <span
                                    class="px-2 py-1 rounded text-xs font-semibold"
                                    :class="empresa.can_access ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                                >
                                    {{ empresa.can_access ? 'Habilitada' : 'Bloqueada' }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <button
                                    type="button"
                                    class="px-3 py-2 text-xs font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700"
                                    @click="selectEmpresa(empresa)"
                                >
                                    Configurar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <Pagination class="mt-6" :links="empresas.links" />
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ selectedEmpresaTitle }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Define vigencia manual cuando la empresa ya pagó fuera de Stripe.
                </p>

                <div v-if="!selectedEmpresa" class="mt-4 text-sm text-gray-600">
                    Selecciona una empresa desde la tabla para editar su vigencia.
                </div>

                <form v-else class="mt-4 space-y-4" @submit.prevent="save">
                    <div>
                        <label class="block font-medium text-sm text-gray-700">Plan</label>
                        <select
                            class="form-select w-full rounded-md shadow-sm"
                            v-model="form.plan_code"
                        >
                            <option :value="null">Sin plan (legacy)</option>
                            <option
                                v-for="planOption in plans"
                                :key="planOption.code"
                                :value="planOption.code"
                            >
                                {{ planOption.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.plan_code" />
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Ciclo</label>
                        <select
                            class="form-select w-full rounded-md shadow-sm"
                            v-model="form.plan_cycle"
                            :disabled="!form.plan_code"
                        >
                            <option value="monthly">Mensual</option>
                            <option value="yearly" :disabled="!selectedPlan?.has_yearly">Anual</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.plan_cycle" />
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Fecha inicio</label>
                        <input
                            type="datetime-local"
                            class="form-input w-full rounded-md shadow-sm"
                            v-model="form.manual_subscription_starts_at"
                            :disabled="form.clear_manual_dates"
                        >
                        <InputError class="mt-2" :message="form.errors.manual_subscription_starts_at" />
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Fecha vencimiento</label>
                        <input
                            type="datetime-local"
                            class="form-input w-full rounded-md shadow-sm"
                            v-model="form.manual_subscription_ends_at"
                            :disabled="form.clear_manual_dates"
                        >
                        <InputError class="mt-2" :message="form.errors.manual_subscription_ends_at" />
                    </div>

                    <label class="inline-flex items-center">
                        <input
                            type="checkbox"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            v-model="form.clear_manual_dates"
                        >
                        <span class="ml-2 text-sm text-gray-700">Limpiar fechas manuales</span>
                    </label>

                    <label class="inline-flex items-center">
                        <input
                            type="checkbox"
                            class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"
                            v-model="form.manual_subscription_blocked"
                        >
                        <span class="ml-2 text-sm text-gray-700">Bloquear empresa manualmente</span>
                    </label>

                    <InputError class="mt-2" :message="form.errors.manual_subscription_blocked" />

                    <button
                        type="submit"
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-gray-900 border border-gray-900 rounded hover:bg-black disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Guardar configuración
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
