<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object,
    subscription: Object,
    plans: Array,
    trialDays: Number,
    canManage: Boolean,
});

const cycle = ref(props.empresa?.plan_cycle ?? 'monthly');
const plan = ref(props.empresa?.plan_code ?? 'campo');

const planData = computed(() => (props.plans || []).find((p) => p.code === plan.value) || null);
watch(planData, (p) => {
    if (!p?.has_yearly && cycle.value === 'yearly') {
        cycle.value = 'monthly';
    }
});
const effectiveCycle = computed(() => (cycle.value === 'yearly' && !planData.value?.has_yearly ? 'monthly' : cycle.value));
const price = computed(() => {
    if (!planData.value) return null;
    return effectiveCycle.value === 'yearly' ? planData.value.prices?.yearly_mxn : planData.value.prices?.monthly_mxn;
});

const currentPlanCode = computed(() => props.empresa?.plan_code ?? null);
const hasPaidSubscription = computed(() => Boolean(props.subscription?.active));
const isCurrentPlan = (code) => hasPaidSubscription.value && currentPlanCode.value === code;

const currentPlanIndex = computed(() => {
    return (props.plans || []).findIndex((p) => p.code === currentPlanCode.value);
});

const selectedPlanIndex = computed(() => {
    return (props.plans || []).findIndex((p) => p.code === plan.value);
});

const changeDirection = computed(() => {
    if (!hasPaidSubscription.value || currentPlanIndex.value < 0 || selectedPlanIndex.value < 0) {
        return null;
    }

    if (selectedPlanIndex.value === currentPlanIndex.value) {
        return null;
    }

    return selectedPlanIndex.value > currentPlanIndex.value ? 'up' : 'down';
});

const checkoutForm = useForm({
    plan: plan.value,
    cycle: effectiveCycle.value,
});

const updatePlan = () => {
    checkoutForm.plan = plan.value;
    checkoutForm.cycle = effectiveCycle.value;
};

const startCheckout = () => {
    if (isCurrentPlan(plan.value)) return;
    updatePlan();
    checkoutForm.post(route('subscription.checkout'), { preserveScroll: true });
};

const openPortal = () => {
    checkoutForm.post(route('subscription.portal'), { preserveScroll: true });
};

const estado = computed(() => {
    if (!props.empresa) return 'Sin empresa';
    if (props.subscription?.active) return 'Suscripcion activa';
    if ((props.empresa.trial_days_left ?? 0) > 0) return 'En prueba';
    return 'Vencida';
});
</script>

<template>
    <AppLayout title="Suscripcion">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mi suscripcion</h2>
        </template>

        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div v-if="!empresa" class="bg-white border rounded-lg p-6">
                No hay empresa activa asociada a tu cuenta.
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Estado -->
                <div class="lg:col-span-1 bg-white border rounded-2xl p-6 shadow-sm">
                    <div class="text-sm font-semibold text-gray-500">Estado</div>
                    <div class="mt-1 text-2xl font-extrabold text-gray-900">{{ estado }}</div>

                    <div v-if="empresa.trial_ends_at" class="mt-4 text-sm text-gray-700">
                        <div class="font-semibold">Trial</div>
                        <div>Finaliza: {{ new Date(empresa.trial_ends_at).toLocaleString() }}</div>
                        <div>Dias restantes: {{ empresa.trial_days_left }}</div>
                    </div>

                    <div v-if="subscription" class="mt-4 text-sm text-gray-700">
                        <div class="font-semibold">Stripe</div>
                        <div>Status: {{ subscription.stripe_status }}</div>
                        <div v-if="subscription.trial_ends_at">Trial (Stripe) termina: {{ new Date(subscription.trial_ends_at).toLocaleString() }}</div>
                        <div v-if="subscription.ends_at">Termina: {{ new Date(subscription.ends_at).toLocaleString() }}</div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3">
                        <button
                            v-if="canManage"
                            type="button"
                            class="px-4 py-3 rounded-lg bg-gray-900 text-white font-bold hover:bg-gray-800 disabled:opacity-50"
                            @click="openPortal"
                            :disabled="checkoutForm.processing || !empresa.has_stripe_id"
                        >
                            Abrir portal de facturacion
                        </button>

                        <div v-else class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg p-3">
                            Solo el administrador de la empresa puede gestionar la suscripcion.
                        </div>
                    </div>
                </div>

                <!-- Planes -->
                <div class="lg:col-span-2 bg-white border rounded-2xl p-6 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold text-gray-500">Selecciona tu plan</div>
                            <div class="mt-1 text-xl font-extrabold text-gray-900">
                                {{ planData?.name ?? 'Plan' }}
                            </div>
                            <div class="mt-1 text-sm text-gray-600" v-if="price !== null">
                                ${{ Number(price).toFixed(2) }} MXN / {{ effectiveCycle === 'yearly' ? 'anio' : 'mes' }}
                            </div>
                        </div>

                        <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 self-start md:self-auto">
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-semibold rounded-md"
                                :class="cycle === 'monthly' ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
                                @click="cycle = 'monthly'"
                            >
                                Mensual
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 text-sm font-semibold rounded-md"
                                :class="cycle === 'yearly' ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
                                @click="cycle = 'yearly'"
                            >
                                Anual
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button
                            v-for="p in plans"
                            :key="p.code"
                            type="button"
                            class="text-left rounded-xl border p-4"
                            :class="[
                                plan === p.code ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 bg-white',
                                isCurrentPlan(p.code) ? 'opacity-60 cursor-not-allowed' : 'hover:shadow-sm',
                            ]"
                            :disabled="isCurrentPlan(p.code)"
                            @click="!isCurrentPlan(p.code) && (plan = p.code)"
                        >
                            <div class="font-extrabold text-gray-900">{{ p.name }}</div>
                            <div class="mt-2 text-sm text-gray-700 space-y-1">
                                <div>Sucursales: hasta {{ p.limits?.max_sucursales }}</div>
                                <div>Dispositivos: {{ p.limits?.devices_per_sucursal }} por sucursal</div>
                            </div>
                            <div class="mt-3 text-xs text-gray-500">
                                {{ p.has_yearly ? 'Incluye anual con descuento' : 'Solo mensual' }}
                            </div>
                            <div
                                v-if="isCurrentPlan(p.code)"
                                class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-900"
                            >
                                Plan actual
                            </div>
                        </button>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row gap-3 items-center justify-between border-t pt-6">
                        <div class="text-sm text-gray-600">
                            <template v-if="hasPaidSubscription">
                                Al cambiar de plan se aplica ajuste proporcional automatico por el tiempo restante del ciclo actual.
                            </template>
                            <template v-else>
                                Si estas en prueba, el cobro se programara al final del trial (si aplica).
                            </template>
                        </div>

                        <div class="flex gap-3 w-full sm:w-auto">
                            <Link
                                :href="route('subscription.onboarding', { step: 2 })"
                                class="px-4 py-3 rounded-lg border border-gray-200 bg-white font-bold text-gray-700 hover:bg-gray-50 text-center w-full sm:w-auto"
                            >
                                Ver onboarding
                            </Link>
                            <button
                                v-if="canManage"
                                type="button"
                                class="px-4 py-3 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700 disabled:opacity-50 w-full sm:w-auto"
                                @click="startCheckout"
                                :disabled="checkoutForm.processing || isCurrentPlan(plan)"
                            >
                                {{ hasPaidSubscription ? 'Cambiar plan' : 'Suscribirme' }}
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="hasPaidSubscription && isCurrentPlan(plan)"
                        class="mt-4 text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg p-3"
                    >
                        Este es tu plan actual. Selecciona un plan superior o inferior.
                    </div>
                    <div
                        v-if="hasPaidSubscription && changeDirection"
                        class="mt-4 text-sm text-emerald-900 bg-emerald-50 border border-emerald-200 rounded-lg p-3"
                    >
                        {{ changeDirection === 'up' ? 'Subida de plan detectada.' : 'Bajada de plan detectada.' }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
