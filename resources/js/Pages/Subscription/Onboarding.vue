<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    plans: Array,
    selected: Object,
    step: Number,
    trialDays: Number,
    termsVersion: String,
    privacyVersion: String,
});

const page = usePage();
const isAuthed = computed(() => Boolean(page.props.auth?.user));

const activeStep = ref(props.step ?? 1);
watch(isAuthed, (v) => {
    if (v) activeStep.value = Math.max(activeStep.value, 2);
});

const plan = ref(props.selected?.plan ?? 'campo');
const cycle = ref(props.selected?.cycle ?? 'monthly');

const planData = computed(() => {
    return (props.plans || []).find((p) => p.code === plan.value) || null;
});

watch(planData, (p) => {
    if (!p?.has_yearly && cycle.value === 'yearly') {
        cycle.value = 'monthly';
    }
});

const effectiveCycle = computed(() => {
    if (cycle.value === 'yearly' && !planData.value?.has_yearly) return 'monthly';
    return cycle.value;
});

const price = computed(() => {
    if (!planData.value) return null;
    return effectiveCycle.value === 'yearly' ? planData.value.prices?.yearly_mxn : planData.value.prices?.monthly_mxn;
});

const form = useForm({
    plan: plan.value,
    cycle: effectiveCycle.value,

    name: '',
    email: '',
    password: '',

    empresa_nombre: '',
    empresa_direccion: '',
    empresa_telefono: '',
    empresa_email: '',

    sucursal_nombre: '',
    sucursal_direccion: '',

    billing_requires_invoice: false,
    billing_email: '',
    billing_razon_social: '',
    billing_rfc: '',
    billing_regimen_fiscal: '',
    billing_uso_cfdi: '',
    billing_codigo_postal: '',

    terms_accepted: false,
    privacy_accepted: false,
});

watch([plan, effectiveCycle], () => {
    form.plan = plan.value;
    form.cycle = effectiveCycle.value;
});

const submitStep1 = () => {
    form.post(route('subscription.onboarding.store'), {
        preserveScroll: true,
        onSuccess: () => {
            activeStep.value = 2;
        },
    });
};

const checkoutStripe = () => {
    if (!isAuthed.value) return;
    router.post(route('subscription.checkout'), { plan: plan.value, cycle: effectiveCycle.value }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Suscribirse" />

    <div class="min-h-screen bg-slate-50 text-slate-900">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-8">
                <div class="flex-1">
                    <h1 class="text-4xl font-extrabold tracking-tight">Inicia tu prueba gratis</h1>
                    <p class="mt-2 text-slate-600">
                        {{ trialDays }} dias gratis. El pago es opcional ahora; te avisaremos antes de que venza.
                    </p>

                    <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">Plan</label>
                                <select v-model="plan" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                    <option v-for="p in plans" :key="p.code" :value="p.code">{{ p.name }}</option>
                                </select>
                                <div v-if="form.errors.plan" class="text-sm text-red-600 mt-1">{{ form.errors.plan }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">Periodo</label>
                                <select v-model="cycle" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                    <option value="monthly">Mensual</option>
                                    <option value="yearly">Anual</option>
                                </select>
                                <div v-if="form.errors.cycle" class="text-sm text-red-600 mt-1">{{ form.errors.cycle }}</div>
                            </div>
                        </div>

                        <div class="mt-4 text-sm text-slate-600">
                            <span class="font-semibold text-slate-800">Precio:</span>
                            <span v-if="price !== null">${{ Number(price).toFixed(2) }} MXN / {{ effectiveCycle === 'yearly' ? 'anio' : 'mes' }}</span>
                            <span v-else>-</span>
                            <span class="ml-2 text-slate-500">(no se cobra hoy durante el trial)</span>
                        </div>
                    </div>

                    <!-- Paso 1 -->
                    <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm">
                        <button
                            type="button"
                            class="w-full flex items-center justify-between px-6 py-5"
                            @click="activeStep = 1"
                        >
                            <div class="text-left">
                                <div class="text-sm font-semibold text-slate-500">Paso 1</div>
                                <div class="text-xl font-extrabold">Crear cuenta y empresa</div>
                            </div>
                            <div class="text-sm font-semibold" :class="activeStep === 1 ? 'text-emerald-700' : 'text-slate-500'">
                                {{ activeStep === 1 ? 'Abierto' : 'Abrir' }}
                            </div>
                        </button>

                        <div v-show="activeStep === 1" class="px-6 pb-6">
                            <div v-if="isAuthed" class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm">
                                Ya tienes sesion iniciada. Puedes continuar con el Paso 2 (pago opcional) o ir al dashboard.
                            </div>

                            <form v-else @submit.prevent="submitStep1" class="mt-4 grid grid-cols-1 gap-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-1">
                                        <label class="block text-sm font-semibold text-slate-700">Nombre</label>
                                        <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required />
                                        <div v-if="form.errors.name" class="text-sm text-red-600 mt-1">{{ form.errors.name }}</div>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-sm font-semibold text-slate-700">Email</label>
                                        <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required />
                                        <div v-if="form.errors.email" class="text-sm text-red-600 mt-1">{{ form.errors.email }}</div>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-sm font-semibold text-slate-700">Contrasena</label>
                                        <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required />
                                        <div v-if="form.errors.password" class="text-sm text-red-600 mt-1">{{ form.errors.password }}</div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-4">
                                    <div class="text-sm font-semibold text-slate-500 mb-2">Datos de la empresa</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Nombre de empresa</label>
                                            <input v-model="form.empresa_nombre" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required />
                                            <div v-if="form.errors.empresa_nombre" class="text-sm text-red-600 mt-1">{{ form.errors.empresa_nombre }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Direccion</label>
                                            <input v-model="form.empresa_direccion" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required />
                                            <div v-if="form.errors.empresa_direccion" class="text-sm text-red-600 mt-1">{{ form.errors.empresa_direccion }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Telefono (opcional)</label>
                                            <input v-model="form.empresa_telefono" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                                            <div v-if="form.errors.empresa_telefono" class="text-sm text-red-600 mt-1">{{ form.errors.empresa_telefono }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Email empresa (opcional)</label>
                                            <input v-model="form.empresa_email" type="email" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                                            <div v-if="form.errors.empresa_email" class="text-sm text-red-600 mt-1">{{ form.errors.empresa_email }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-4">
                                    <div class="flex items-center gap-3">
                                        <input id="billing_requires_invoice" v-model="form.billing_requires_invoice" type="checkbox" class="rounded border-slate-300" />
                                        <label for="billing_requires_invoice" class="text-sm font-semibold text-slate-700">
                                            Requiero factura de la suscripcion (opcional)
                                        </label>
                                    </div>

                                    <div v-if="form.billing_requires_invoice" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Email de facturacion</label>
                                            <input v-model="form.billing_email" type="email" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                                            <div v-if="form.errors.billing_email" class="text-sm text-red-600 mt-1">{{ form.errors.billing_email }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Razon social</label>
                                            <input v-model="form.billing_razon_social" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                                            <div v-if="form.errors.billing_razon_social" class="text-sm text-red-600 mt-1">{{ form.errors.billing_razon_social }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">RFC</label>
                                            <input v-model="form.billing_rfc" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                                            <div v-if="form.errors.billing_rfc" class="text-sm text-red-600 mt-1">{{ form.errors.billing_rfc }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Codigo postal</label>
                                            <input v-model="form.billing_codigo_postal" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                                            <div v-if="form.errors.billing_codigo_postal" class="text-sm text-red-600 mt-1">{{ form.errors.billing_codigo_postal }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Regimen fiscal</label>
                                            <input v-model="form.billing_regimen_fiscal" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                                            <div v-if="form.errors.billing_regimen_fiscal" class="text-sm text-red-600 mt-1">{{ form.errors.billing_regimen_fiscal }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Uso CFDI</label>
                                            <input v-model="form.billing_uso_cfdi" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                                            <div v-if="form.errors.billing_uso_cfdi" class="text-sm text-red-600 mt-1">{{ form.errors.billing_uso_cfdi }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-4 space-y-3">
                                    <div class="flex items-start gap-3">
                                        <input id="terms" v-model="form.terms_accepted" type="checkbox" class="mt-1 rounded border-slate-300" />
                                        <label for="terms" class="text-sm text-slate-700">
                                            Acepto los
                                            <Link :href="route('legal.terms')" class="font-semibold text-emerald-700 hover:underline">Terminos y Condiciones</Link>
                                            (v{{ termsVersion }}).
                                        </label>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <input id="privacy" v-model="form.privacy_accepted" type="checkbox" class="mt-1 rounded border-slate-300" />
                                        <label for="privacy" class="text-sm text-slate-700">
                                            Acepto el
                                            <Link :href="route('legal.privacy')" class="font-semibold text-emerald-700 hover:underline">Aviso de Privacidad</Link>
                                            (v{{ privacyVersion }}).
                                        </label>
                                    </div>
                                    <div v-if="form.errors.terms_accepted" class="text-sm text-red-600">{{ form.errors.terms_accepted }}</div>
                                    <div v-if="form.errors.privacy_accepted" class="text-sm text-red-600">{{ form.errors.privacy_accepted }}</div>
                                </div>

                                <div class="pt-2 flex items-center justify-between gap-3">
                                    <Link :href="route('login')" class="text-sm font-semibold text-slate-600 hover:underline">
                                        Ya tengo cuenta
                                    </Link>
                                    <button
                                        type="submit"
                                        class="px-5 py-3 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700 disabled:opacity-50"
                                        :disabled="form.processing"
                                    >
                                        Crear cuenta
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Paso 2 -->
                    <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm">
                        <button
                            type="button"
                            class="w-full flex items-center justify-between px-6 py-5"
                            @click="activeStep = 2"
                        >
                            <div class="text-left">
                                <div class="text-sm font-semibold text-slate-500">Paso 2</div>
                                <div class="text-xl font-extrabold">Pago (opcional)</div>
                            </div>
                            <div class="text-sm font-semibold" :class="activeStep === 2 ? 'text-emerald-700' : 'text-slate-500'">
                                {{ activeStep === 2 ? 'Abierto' : 'Abrir' }}
                            </div>
                        </button>

                        <div v-show="activeStep === 2" class="px-6 pb-6">
                            <div v-if="!isAuthed" class="p-4 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 text-sm">
                                Primero crea tu cuenta (Paso 1) para poder continuar con el pago.
                            </div>

                            <div v-else class="space-y-4">
                                <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm">
                                    Si te suscribes ahora, tu primer cobro se programara al final de tu prueba (si aun esta vigente).
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3">
                                    <button
                                        type="button"
                                        class="px-5 py-3 rounded-lg bg-slate-900 text-white font-bold hover:bg-slate-800"
                                        @click="checkoutStripe"
                                        :disabled="form.processing"
                                    >
                                        Suscribirme con Stripe
                                    </button>
                                    <Link
                                        :href="route('dashboard')"
                                        class="px-5 py-3 rounded-lg border border-slate-200 bg-white font-bold text-slate-700 hover:bg-slate-50 text-center"
                                    >
                                        Omitir por ahora
                                    </Link>
                                    <Link
                                        :href="route('subscription.show')"
                                        class="px-5 py-3 rounded-lg border border-emerald-200 bg-emerald-50 font-bold text-emerald-800 hover:bg-emerald-100 text-center"
                                    >
                                        Ver mi suscripcion
                                    </Link>
                                </div>

                                <div class="text-xs text-slate-500">
                                    Nota: PayPal se integra en una fase posterior.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen -->
                <aside class="w-full md:w-80">
                    <div class="sticky top-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <div class="text-sm font-semibold text-slate-500">Resumen</div>
                        <div class="mt-1 text-xl font-extrabold">{{ planData?.name ?? 'Plan' }}</div>
                        <div class="mt-3 text-sm text-slate-700 space-y-2" v-if="planData">
                            <div><span class="font-semibold">Periodo:</span> {{ effectiveCycle === 'yearly' ? 'Anual' : 'Mensual' }}</div>
                            <div><span class="font-semibold">Precio:</span> ${{ Number(price).toFixed(2) }} MXN</div>
                            <div><span class="font-semibold">Trial:</span> {{ trialDays }} dias gratis</div>
                            <div><span class="font-semibold">Sucursales:</span> hasta {{ planData.limits?.max_sucursales }}</div>
                            <div><span class="font-semibold">Dispositivos:</span> {{ planData.limits?.devices_per_sucursal }} por sucursal</div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>
