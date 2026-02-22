<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    plans: Array,
    trialDays: Number,
    defaults: Object,
    viewer: Object,
});

const cycle = ref(props.defaults?.cycle ?? 'monthly'); // monthly|yearly
const showTrialBenefits = computed(() => Boolean(props.viewer?.show_trial_benefits));
const isAuthenticated = computed(() => Boolean(props.viewer?.is_authenticated));
const activePlanCode = computed(() => props.viewer?.active_plan_code ?? null);
const activePlanName = computed(() => props.viewer?.active_plan_name ?? null);
const activePlanCycle = computed(() => props.viewer?.active_plan_cycle ?? null);

const activePlanLabel = computed(() => {
    if (!activePlanName.value) return null;
    const cycleLabel = activePlanCycle.value === 'yearly' ? 'anual' : (activePlanCycle.value === 'monthly' ? 'mensual' : null);
    return cycleLabel ? `${activePlanName.value} (${cycleLabel})` : activePlanName.value;
});

const normalizedPlans = computed(() => {
    return (props.plans || []).map((p) => {
        const hasYearly = Boolean(p.has_yearly);
        const effectiveCycle = cycle.value === 'yearly' && !hasYearly ? 'monthly' : cycle.value;
        const price = effectiveCycle === 'yearly' ? p.prices?.yearly_mxn : p.prices?.monthly_mxn;
        const isActivePlan = Boolean(activePlanCode.value) && activePlanCode.value === p.code;
        return { ...p, effectiveCycle, price, isActivePlan };
    });
});
</script>

<template>
    <Head title="Planes" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-4xl font-extrabold tracking-tight">Planes AgroSys</h1>
                    <p v-if="showTrialBenefits" class="mt-2 text-slate-600">
                        {{ trialDays }} dias gratis. Sin tarjeta para iniciar. Recibe recordatorios antes de vencer.
                    </p>
                    <p v-else-if="isAuthenticated && activePlanLabel" class="mt-2 text-slate-600">
                        Plan actual: <span class="font-semibold text-slate-800">{{ activePlanLabel }}</span>.
                    </p>
                    <p v-else class="mt-2 text-slate-600">
                        Selecciona un plan y gestiona tu suscripcion.
                    </p>
                </div>

                <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 self-start md:self-auto">
                    <button
                        class="px-4 py-2 text-sm font-semibold rounded-md"
                        :class="cycle === 'monthly' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
                        @click="cycle = 'monthly'"
                        type="button"
                    >
                        Mensual
                    </button>
                    <button
                        class="px-4 py-2 text-sm font-semibold rounded-md"
                        :class="cycle === 'yearly' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'"
                        @click="cycle = 'yearly'"
                        type="button"
                    >
                        Anual
                    </button>
                </div>
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="plan in normalizedPlans" :key="plan.code" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold">{{ plan.name }}</h2>
                            <p class="text-sm text-slate-500 mt-1">
                                {{ plan.effectiveCycle === 'yearly' ? 'Pago anual' : 'Pago mensual' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-extrabold">
                                ${{ Number(plan.price).toFixed(2) }}
                            </div>
                            <div class="text-xs text-slate-500">
                                MXN / {{ plan.effectiveCycle === 'yearly' ? 'anio' : 'mes' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 text-sm text-slate-700 space-y-2">
                        <div><span class="font-semibold">Sucursales:</span> hasta {{ plan.limits?.max_sucursales }}</div>
                        <div><span class="font-semibold">Dispositivos:</span> {{ plan.limits?.devices_per_sucursal }} por sucursal</div>
                        <div v-if="plan.features?.bitacora"><span class="font-semibold">Bitacora:</span> incluida</div>
                        <div v-if="plan.features?.soporte_12h_6d"><span class="font-semibold">Soporte:</span> 12h / 6 dias</div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-between gap-3">
                        <Link
                            v-if="!isAuthenticated"
                            :href="route('subscription.onboarding', { plan: plan.code, cycle: plan.effectiveCycle })"
                            class="w-full text-center px-4 py-3 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700"
                        >
                            Iniciar prueba gratis
                        </Link>
                        <button
                            v-else-if="plan.isActivePlan"
                            type="button"
                            class="w-full text-center px-4 py-3 rounded-lg bg-amber-100 text-amber-800 font-bold cursor-not-allowed"
                            disabled
                        >
                            Plan activo
                        </button>
                        <Link
                            v-else
                            :href="route('subscription.show')"
                            class="w-full text-center px-4 py-3 rounded-lg bg-slate-900 text-white font-bold hover:bg-slate-800"
                        >
                            Gestionar suscripcion
                        </Link>
                    </div>
                </div>
            </div>

            <div class="mt-10 text-sm text-slate-600">
                <Link v-if="!isAuthenticated" :href="route('login')" class="font-semibold text-emerald-700 hover:underline">
                    Ya tienes cuenta? Inicia sesion
                </Link>
                <Link v-else :href="route('subscription.show')" class="font-semibold text-emerald-700 hover:underline">
                    Ir a mi suscripcion
                </Link>
            </div>
        </div>
    </div>
</template>
