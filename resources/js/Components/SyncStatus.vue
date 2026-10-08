<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useSyncStore } from '@/stores/sync';
import { useConnectivityStore } from '@/stores/connectivity';
import { offlineSaleRepository } from '@/Offline/repositories/OfflineSaleRepository';
import { printProvisionalTicket } from '@/Offline/services/provisionalTicket';
import { notify } from '@/utils/notify';

const sync = useSyncStore();
const connectivity = useConnectivityStore();
const open = ref(false);
const sales = ref([]);
let timer;

async function refresh() {
    await sync.refreshCount();
    connectivity.pendingCount = sync.pendingCount;
    sales.value = await offlineSaleRepository.listLocalSales().catch(() => []);
}
async function retry() {
    if (!['online', 'sync_error'].includes(connectivity.mode)) return notify('Todavía no hay conexión utilizable con Agrosys.', 'error');
    try { const result = connectivity.mode === 'sync_error' ? await sync.retryBlocked() : await sync.run(); notify(result.conflicts ? 'Sincronización parcial: hay operaciones que requieren revisión.' : 'Sincronización completada.', result.conflicts ? 'error' : 'success'); await refresh(); }
    catch { notify('No fue posible sincronizar. Las operaciones permanecen guardadas.', 'error'); }
}
async function cancelSale(sale) {
    const pregunta = sale.status === 'conflict'
        ? `El servidor no aceptó la venta ${sale.localFolio}, así que no quedó registrada. ¿Descartarla de este dispositivo? Si el cliente sí pagó, vuelve a capturarla en línea.`
        : `¿Cancelar la venta local ${sale.localFolio}?`;
    if (!window.confirm(pregunta)) return;
    try {
        await offlineSaleRepository.cancelUnsynced(sale.id);
        await refresh();
        notify(sale.status === 'conflict' ? 'Venta descartada de este dispositivo.' : 'Venta local cancelada; la existencia estimada fue compensada.', 'success');
        if (connectivity.mode === 'sync_error') await connectivity.healthCheck();
    }
    catch { notify('Esta venta ya no puede cancelarse localmente.', 'error'); }
}
function reprint(sale) {
    const payload = sale.operation?.payload;
    if (!payload) return;
    printProvisionalTicket({ ...payload, items: payload.items ?? [], occurredAt: sale.occurredAt, deviceId: sale.deviceId, total: sale.total }, { localFolio: sale.localFolio }, payload.ticket ?? { branchName: 'Sucursal local' });
}
const openPanel = () => { open.value = true; refresh(); };
onMounted(() => { refresh(); timer = window.setInterval(refresh, 15000); window.addEventListener('agrosys:open-sync', openPanel); });
onUnmounted(() => { window.clearInterval(timer); window.removeEventListener('agrosys:open-sync', openPanel); });
</script>

<template>
    <button v-if="sync.pendingCount || connectivity.isLimited" @click="open = true; refresh()" class="fixed bottom-4 right-4 z-50 rounded-full bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-xl">
        Sincronización · {{ sync.pendingCount }}
    </button>
    <div v-if="open" class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4" @click.self="open = false">
        <section class="max-h-[85vh] w-full max-w-3xl overflow-auto rounded-lg bg-white p-5 shadow-2xl">
            <div class="flex items-center justify-between"><h2 class="text-lg font-bold">Ventas locales y sincronización</h2><button @click="open = false">✕</button></div>
            <div class="my-4 flex gap-3"><button @click="retry" :disabled="sync.syncing || !['online', 'sync_error'].includes(connectivity.mode)" class="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-50">{{ sync.syncing ? 'Sincronizando…' : 'Sincronizar ahora' }}</button><span class="text-sm text-gray-500">Estado: {{ connectivity.mode }}</span></div>
            <p v-if="!sales.length" class="py-8 text-center text-gray-500">No hay ventas locales.</p>
            <article v-for="sale in sales" :key="sale.id" class="mb-3 rounded border p-3">
                <div class="flex flex-wrap justify-between gap-2"><div><strong>{{ sale.localFolio }}</strong><div class="text-xs text-gray-500">{{ new Date(sale.occurredAt).toLocaleString('es-MX') }}</div></div><span class="rounded bg-gray-100 px-2 py-1 text-xs">{{ sale.status }}</span></div>
                <p class="mt-2">Total: ${{ Number(sale.total).toFixed(2) }}</p>
                <p v-if="sale.operation?.lastErrorMessage" class="mt-1 text-sm text-red-700">{{ sale.operation.lastErrorMessage }}</p>
                <div class="mt-3 flex gap-2"><button @click="reprint(sale)" class="rounded border px-3 py-1 text-sm">Reimprimir</button><button v-if="sale.status === 'pending_sync'" @click="cancelSale(sale)" class="rounded border border-red-300 px-3 py-1 text-sm text-red-700">Cancelar local</button><button v-if="sale.status === 'conflict'" @click="cancelSale(sale)" class="rounded border border-red-300 px-3 py-1 text-sm text-red-700">Descartar</button></div>
            </article>
        </section>
    </div>
</template>
