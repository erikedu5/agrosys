<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useConnectivityStore } from '@/stores/connectivity';

const connectivity = useConnectivityStore();
const page = usePage();
const offlineFeaturesEnabled = computed(() => Boolean(page.props.offline?.enabled));
const message = computed(() => ({
    offline: offlineFeaturesEnabled.value
        ? 'SIN CONEXIÓN · Agrosys opera en modo limitado. Las ventas se guardarán localmente.'
        : 'SIN CONEXIÓN · Las funciones que requieren el servidor no están disponibles.',
    recovering: `Conexión detectada. Verificando Agrosys${connectivity.pendingCount ? ` y ${connectivity.pendingCount} pendientes` : ''}...`,
    sync_error: 'Hay conexión, pero Agrosys no pudo completar la recuperación. Algunas funciones siguen bloqueadas.',
}[connectivity.mode]));
const color = computed(() => connectivity.mode === 'sync_error' ? 'bg-red-700' : connectivity.mode === 'recovering' ? 'bg-blue-700' : 'bg-amber-600');
const formattedLastSync = computed(() => connectivity.lastSyncAt ? new Date(connectivity.lastSyncAt).toLocaleString('es-MX') : 'sin sincronización previa');
</script>

<template>
    <div v-if="connectivity.mode !== 'online'" :class="color" class="fixed inset-x-0 top-0 z-[100] min-h-12 px-4 py-3 text-sm text-white shadow-xl" role="alert" aria-live="assertive">
        <div class="mx-auto flex max-w-8xl flex-wrap items-center justify-between gap-2">
            <strong class="flex items-center gap-2"><span class="inline-block h-3 w-3 animate-pulse rounded-full bg-white" aria-hidden="true"></span>{{ message }}</strong>
            <span>Última sincronización: {{ formattedLastSync }} · Pendientes: {{ connectivity.pendingCount }}</span>
        </div>
    </div>
</template>
