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
const hasStripeSubscription = computed(() => Boolean(props.subscription?.active));
const hasManualSubscription = computed(() => Boolean(props.empresa?.manual_subscription_active));
const hasPaidSubscription = computed(() => hasStripeSubscription.value || hasManualSubscription.value);
const isHybridSubscription = computed(() => hasStripeSubscription.value && hasManualSubscription.value);
const isStripeCurrentPlan = (code) => hasStripeSubscription.value && currentPlanCode.value === code;
const isAssignedPlan = (code) => Boolean(currentPlanCode.value) && currentPlanCode.value === code;

const currentPlanIndex = computed(() => {
    return (props.plans || []).findIndex((p) => p.code === currentPlanCode.value);
});

const selectedPlanIndex = computed(() => {
    return (props.plans || []).findIndex((p) => p.code === plan.value);
});

const changeDirection = computed(() => {
    if (!hasStripeSubscription.value || currentPlanIndex.value < 0 || selectedPlanIndex.value < 0) {
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
    if (isStripeCurrentPlan(plan.value)) return;
    updatePlan();
    checkoutForm.post(route('subscription.checkout'), { preserveScroll: true });
};

const openPortal = () => {
    checkoutForm.post(route('subscription.portal'), { preserveScroll: true });
};

const formatDateTime = (value) => {
    if (!value) return null;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return null;
    return date.toLocaleString('es-MX');
};

const formatLongDate = (value) => {
    if (!value) return null;
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return null;

    const base = date.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });

    return base.replace(/ de ([a-zA-Záéíóúñ]+)/, (match, month) => {
        const normalized = `${month.charAt(0).toUpperCase()}${month.slice(1)}`;
        return ` de ${normalized}`;
    });
};

const subscriptionSourceLabel = computed(() => {
    if (!props.empresa) return 'N/A';
    if (hasStripeSubscription.value) return 'Stripe';
    if (hasPaidSubscription.value) return 'Sistema';
    if ((props.empresa.trial_days_left ?? 0) > 0) return 'Trial';
    return 'Sin suscripcion activa';
});

const subscriptionTypeLabel = computed(() => {
    if (!props.empresa) return 'N/A';
    if (hasPaidSubscription.value) return 'Suscripcion activa';
    if ((props.empresa.trial_days_left ?? 0) > 0) return 'Suscripcion en prueba';
    return 'Sin suscripcion';
});

const vigenciaLabel = computed(() => {
    if (!props.empresa) return 'Sin vigencia';

    const endDate =
        props.empresa.manual_subscription_ends_at ||
        props.subscription?.ends_at ||
        props.empresa.trial_ends_at ||
        null;

    const longDate = formatLongDate(endDate);
    if (!longDate) return 'Sin vigencia';

    return `Vence: ${longDate}`;
});

const estado = computed(() => {
    if (!props.empresa) return 'Sin empresa';
    if (props.empresa.manual_subscription_blocked) return 'Bloqueada por administrador';
    if (hasPaidSubscription.value) return 'Suscripcion Activa';
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
                    <div class="mt-2 text-sm text-gray-600">
                        {{ subscriptionTypeLabel }}
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        Fuente: <span class="font-semibold">{{ subscriptionSourceLabel }}</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        Vigencia: <span class="font-semibold">{{ vigenciaLabel }}</span>
                    </div>

                    <div v-if="empresa.trial_ends_at" class="mt-4 text-sm text-gray-700">
                        <div class="font-semibold">Trial</div>
                        <div>Finaliza: {{ formatDateTime(empresa.trial_ends_at) }}</div>
                        <div>Dias restantes: {{ empresa.trial_days_left }}</div>
                    </div>

                    <div v-if="subscription" class="mt-4 text-sm text-gray-700">
                        <div class="font-semibold">Stripe</div>
                        <div>Status: {{ subscription.stripe_status }}</div>
                        <div v-if="subscription.trial_ends_at">Trial (Stripe) termina: {{ formatDateTime(subscription.trial_ends_at) }}</div>
                        <div v-if="subscription.ends_at">Termina: {{ formatDateTime(subscription.ends_at) }}</div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3">
                        <button
                            v-if="canManage && hasStripeSubscription"
                            type="button"
                            class="px-4 py-3 rounded-lg bg-gray-900 text-white font-bold hover:bg-gray-800 disabled:opacity-50"
                            @click="openPortal"
                            :disabled="checkoutForm.processing || !empresa.has_stripe_id"
                        >
                            Abrir portal de facturacion
                        </button>

                        <div v-else-if="!canManage" class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg p-3">
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
                                isStripeCurrentPlan(p.code) ? 'opacity-60 cursor-not-allowed' : 'hover:shadow-sm',
                            ]"
                            :disabled="isStripeCurrentPlan(p.code)"
                            @click="!isStripeCurrentPlan(p.code) && (plan = p.code)"
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
                                v-if="isAssignedPlan(p.code)"
                                class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-900"
                            >
                                {{ hasStripeSubscription ? 'Plan actual' : 'Plan asignado' }}
                            </div>
                        </button>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row gap-3 items-center justify-between border-t pt-6">
                        <div class="text-sm text-gray-600">
                            <template v-if="hasStripeSubscription">
                                Al cambiar de plan se aplica ajuste proporcional automatico por el tiempo restante del ciclo actual.
                            </template>
                            <template v-else-if="hasPaidSubscription">
                                Tu suscripcion esta activa.
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
                                :disabled="checkoutForm.processing || isStripeCurrentPlan(plan)"
                            >
                                {{ hasStripeSubscription ? 'Cambiar plan (Stripe)' : 'Suscribirme con Stripe' }}
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="hasStripeSubscription && isStripeCurrentPlan(plan)"
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
