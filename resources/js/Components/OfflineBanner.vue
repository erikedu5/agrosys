<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useConnectivityStore } from '@/stores/connectivity';

const connectivity = useConnectivityStore();
const page = usePage();
const offlineFeaturesEnabled = computed(() => Boolean(page.props.offline?.enabled));

const syncErrorMessage = computed(() => {
    const issue = connectivity.syncIssue;
    if (issue?.type === 'conflicts') {
        return issue.count === 1
            ? 'Hay 1 venta hecha sin conexión que el servidor no aceptó. Puedes seguir trabajando; revísala en Sincronización.'
            : `Hay ${issue.count} ventas hechas sin conexión que el servidor no aceptó. Puedes seguir trabajando; revísalas en Sincronización.`;
    }
    if (issue?.type === 'session') {
        return 'Tu sesión expiró. Vuelve a iniciar sesión para que Agrosys pueda sincronizar.';
    }
    const detail = issue?.status ? ` (código ${issue.status}${issue.message ? `: ${issue.message}` : ''})` : '';
    return `Agrosys está en línea, pero no se pudo actualizar el modo sin conexión${detail}. Puedes seguir trabajando normalmente.`;
});

const message = computed(() => ({
    offline: offlineFeaturesEnabled.value
        ? 'SIN CONEXIÓN · Agrosys opera en modo limitado. Las ventas se guardarán localmente.'
        : 'SIN CONEXIÓN · Las funciones que requieren el servidor no están disponibles.',
    recovering: `Conexión detectada. Verificando Agrosys${connectivity.pendingCount ? ` y ${connectivity.pendingCount} pendientes` : ''}...`,
    sync_error: syncErrorMessage.value,
}[connectivity.mode]));
const color = computed(() => connectivity.mode === 'sync_error' ? 'bg-amber-800' : connectivity.mode === 'recovering' ? 'bg-blue-700' : 'bg-amber-600');
const formattedLastSync = computed(() => connectivity.lastSyncAt ? new Date(connectivity.lastSyncAt).toLocaleString('es-MX') : 'sin sincronización previa');

const abrirSincronizacion = () => window.dispatchEvent(new CustomEvent('agrosys:open-sync'));
const reintentar = () => connectivity.healthCheck({ syncCatalog: Boolean(page.props.offline?.catalogEnabled) });
</script>

<template>
    <div v-if="connectivity.mode !== 'online'" :class="color" class="fixed inset-x-0 top-0 z-[100] min-h-12 px-4 py-3 text-sm text-white shadow-xl" role="alert" aria-live="assertive">
        <div class="mx-auto flex max-w-8xl flex-wrap items-center justify-between gap-2">
            <strong class="flex items-center gap-2">
                <span class="inline-block h-3 w-3 shrink-0 rounded-full bg-white" :class="connectivity.mode === 'sync_error' ? '' : 'animate-pulse'" aria-hidden="true"></span>
                {{ message }}
            </strong>
            <span v-if="connectivity.mode !== 'sync_error'">Última sincronización: {{ formattedLastSync }} · Pendientes: {{ connectivity.pendingCount }}</span>
            <span v-else class="flex shrink-0 items-center gap-2">
                <a v-if="connectivity.syncIssue?.type === 'session'" :href="route('login')"
                    class="rounded-md bg-white px-3 py-1.5 font-semibold text-amber-900 hover:bg-amber-50">Iniciar sesión</a>
                <button v-else-if="connectivity.syncIssue?.type === 'conflicts'" type="button" @click="abrirSincronizacion"
                    class="rounded-md bg-white px-3 py-1.5 font-semibold text-amber-900 hover:bg-amber-50">Revisar ventas</button>
                <button v-else type="button" @click="reintentar"
                    class="rounded-md bg-white px-3 py-1.5 font-semibold text-amber-900 hover:bg-amber-50">Reintentar</button>
            </span>
        </div>
    </div>
</template>
