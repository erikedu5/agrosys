<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const data = computed(() => page.props.subscription || null);

const shouldShow = computed(() => {
    const s = data.value;
    if (!s) return false;
    if (!s.plan_code) return false;
    if (s.subscribed) return false;
    if (!s.on_trial) return false;
    const left = Number(s.trial_days_left ?? 0);
    return left > 0 && left <= 7;
});

const daysLeft = computed(() => Number(data.value?.trial_days_left ?? 0));

const message = computed(() => {
    if (daysLeft.value === 1) return 'Tu prueba termina en 1 dia. Suscribete para no interrumpir el servicio.';
    return `Tu prueba termina en ${daysLeft.value} dias. Suscribete para no interrumpir el servicio.`;
});
</script>

<template>
    <div v-if="shouldShow" class="bg-amber-50 border-b border-amber-200">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
            <div class="text-sm text-amber-900">
                <span class="font-semibold">Aviso:</span>
                {{ message }}
            </div>
            <div class="shrink-0">
                <Link :href="route('subscription.show')" class="px-3 py-2 text-sm font-semibold text-white bg-amber-600 rounded-md hover:bg-amber-700">
                    Ver suscripcion
                </Link>
            </div>
        </div>
    </div>
</template>

