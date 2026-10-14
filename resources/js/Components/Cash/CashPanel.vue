<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { rememberCashSession } from '@/Offline/services/cashSession';
import { offlineSaleRepository } from '@/Offline/repositories/OfflineSaleRepository';

const props = defineProps({ cash: { type: Object, default: null } });
const emit = defineEmits(['changed', 'busy-change']);
const page = usePage();
const helpPinned = ref(false), helpHovered = ref(false), helpFocused = ref(false);
const helpVisible = computed(() => helpPinned.value || helpHovered.value || helpFocused.value);
const closeHelp = () => { helpPinned.value = false; helpHovered.value = false; helpFocused.value = false; };
const cash = ref(props.cash ?? { current: null, history: [], movements: [] });
const loading = ref(!props.cash), available = ref(!!props.cash);
const opening = ref('0.00'), counted = ref(''), notes = ref('');
const kind = ref('out'), amount = ref(''), description = ref('');
const busy = ref(false), error = ref(''), success = ref('');
const storageKey = `cash-request:${page.props.auth.user.id}:${page.props.sucursalActiva.id}`;
const pending = ref(null);
try { pending.value = JSON.parse(localStorage.getItem(storageKey) ?? 'null'); } catch { error.value = 'No se pudo recuperar la solicitud de caja. Revisa el estado antes de guardar.'; }
const current = computed(() => cash.value.current);
watch([current, pending], () => emit('changed', { ...(available.value ? { sessionId: current.value?.id ?? null } : {}), pending: !!pending.value }), { immediate: true });
watch([busy, loading], () => emit('busy-change', busy.value || loading.value), { immediate: true });
onMounted(async () => {
    loading.value = true;
    try { await refresh(); } catch { error.value = 'Conéctate para abrir, actualizar o cerrar la caja.'; }
    finally { loading.value = false; }
});
const labels = { sale: 'Ventas de contado', credit_initial: 'Iniciales de crédito', account_payment: 'Abonos de clientes', refund: 'Reembolsos', in: 'Entradas manuales', out: 'Salidas manuales' };
const money = value => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value ?? 0));
const date = value => value ? new Date(value).toLocaleString('es-MX') : '';
const printable = ref(null);
const printCut = session => { printable.value = session; setTimeout(() => window.print(), 0); };

async function refresh() {
    const { data } = await axios.post(route('cash.status'));
    available.value = true;
    cash.value = data;
    rememberCashSession(page.props.auth.user.id, page.props.sucursalActiva.id, data.current?.id);
}
async function submit(action, body) {
    if (!available.value || busy.value || loading.value || pending.value) return;
    error.value = ''; success.value = '';
    try {
        if (action === 'close' && page.props.offline?.salesEnabled) {
            const sales = await offlineSaleRepository.listLocalSales();
            if (sales.some(s => String(s.branchId) === String(page.props.sucursalActiva.id)
                && String(s.userId) === String(page.props.auth.user.id)
                && !['confirmed', 'cancelled_local'].includes(s.status))) {
                throw new Error('Sincroniza y resuelve las ventas pendientes de este navegador antes del corte.');
            }
        }
        if (pending.value || busy.value) return;
        const request = { action, body: { ...body, operation_id: crypto.randomUUID() } };
        // Preserve the exact request before sending; an uncertain response can be retried.
        localStorage.setItem(storageKey, JSON.stringify(request));
        pending.value = request;
        await retry();
    } catch (e) { error.value = e.message; }
}
async function retry() {
    if (!pending.value || busy.value) return;
    busy.value = true; error.value = ''; success.value = '';
    const request = pending.value;
    try {
        const { data } = await axios.post(route('cash.write', { action: request.action }), request.body, { timeout: 20000 });
        rememberCashSession(page.props.auth.user.id, page.props.sucursalActiva.id, data.session.closedAt ? null : data.sessionId);
        localStorage.removeItem(storageKey);
        pending.value = null;
        cash.value = { ...cash.value, current: data.session.closedAt ? null : data.session,
            ...(data.session.closedAt ? { history: [data.session, ...cash.value.history.filter(s => s.id !== data.sessionId)] } : {}) };
        if (request.action === 'close') printable.value = data.session;
        success.value = request.action === 'close' ? 'Corte guardado.' : 'Movimiento de caja guardado.';
        amount.value = ''; description.value = ''; counted.value = ''; notes.value = '';
        await refresh();
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors ?? {}).flat()[0] ?? e.response?.data?.message ?? 'No llegó confirmación. Reintenta la misma solicitud.';
        if ([403, 404, 409, 422].includes(e.response?.status)) {
            localStorage.removeItem(storageKey); pending.value = null;
            await refresh().catch(() => {});
        }
    } finally { busy.value = false; }
}
</script>

<template>
    <section>

            <div class="relative" @keydown.esc="closeHelp">
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-semibold">Apertura y corte de caja</h2>
                    <button type="button" aria-label="Información sobre la caja" aria-describedby="cash-help"
                        :aria-expanded="helpVisible" aria-controls="cash-help"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-green-700 hover:bg-green-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-green-700"
                        @pointerenter="helpHovered = $event.pointerType === 'mouse'"
                        @pointerleave="helpHovered = false"
                        @focus="helpFocused = $event.target.matches(':focus-visible')"
                        @blur="closeHelp" @click="helpPinned = !helpPinned">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 11v6M12 7h.01" />
                        </svg>
                    </button>
                </div>
                <div v-show="helpVisible" id="cash-help" role="tooltip"
                    class="absolute left-0 top-full z-50 mt-1 w-96 max-w-full space-y-3 rounded-xl border border-green-100 bg-white p-4 text-sm font-normal leading-relaxed text-gray-600 shadow-lg"
                    @pointerenter="helpHovered = $event.pointerType === 'mouse'" @pointerleave="helpHovered = false">
                    <p>Caja de {{ page.props.auth.user.name }} · {{ page.props.sucursalActiva.nombre }}. Apertura, movimientos y corte requieren conexión.</p>
                    <p>Antes de cerrar, sincroniza las ventas de todos los dispositivos de este cajero. Abre la caja antes de realizar ventas.</p>
                    <p>Si pagas a un proveedor con efectivo de esta caja, registra una salida manual.</p>
                </div>
            </div>
        <div class="cash-page mx-auto max-w-6xl space-y-6 px-4 py-6">
            <p v-if="error" role="alert" class="rounded bg-red-50 p-3 text-red-800">{{ error }}</p>
            <p v-if="success" role="status" class="rounded bg-green-50 p-3 text-green-800">{{ success }}</p>
            <div v-if="pending" class="rounded border border-amber-300 bg-amber-50 p-4">
                Hay una solicitud sin confirmar. <button class="font-semibold underline" :disabled="busy" @click="retry">Reintentar solicitud</button>
            </div>
            <button type="button" class="text-sm font-semibold text-green-700 underline" :disabled="busy" @click="refresh().catch(() => error = 'No se pudo actualizar la caja.')">Actualizar caja</button>
            <form v-if="!current" class="space-y-4 rounded-xl bg-white p-6 shadow" @submit.prevent="submit('open', { opening })">
                <h3 class="text-lg font-semibold">Abrir caja</h3>
                <label class="block">Fondo inicial ($)<input v-model="opening" aria-label="Fondo inicial" type="number" min="0" max="99999999.99" step="0.01" required class="mt-1 block w-full rounded border-gray-300" :disabled="!available || busy || loading || !!pending"></label>
                <button class="min-h-14 w-full rounded-xl bg-green-700 px-6 py-3 text-lg font-bold text-white disabled:opacity-50 sm:w-auto" :disabled="!available || busy || loading || !!pending">Abrir caja</button>
            </form>
            <template v-else>
                <section class="rounded-xl bg-white p-6 shadow">
                    <h3 class="text-lg font-semibold">Caja abierta · {{ date(current.openedAt) }}</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div><p class="text-sm text-gray-500">Fondo inicial</p><p class="text-xl font-bold">{{ money(current.opening) }}</p></div>
                        <div><p class="text-sm text-gray-500">Cobros de ventas y abonos</p><p class="text-xl font-bold">{{ money(Number(current.totals.sale ?? 0) + Number(current.totals.credit_initial ?? 0) + Number(current.totals.account_payment ?? 0)) }}</p></div>
                        <div><p class="text-sm text-gray-500">Efectivo esperado</p><p class="text-2xl font-bold text-green-700">{{ money(current.expected) }}</p></div>
                    </div>
                </section>
                <div class="grid gap-6 md:grid-cols-2">
                    <form class="space-y-4 rounded-xl bg-white p-6 shadow" @submit.prevent="submit('movement', { session_id: current.id, kind, amount, description })">
                        <h3 class="text-lg font-semibold">Entrada o salida de efectivo</h3>
                        <fieldset :disabled="!available || busy || loading || !!pending" class="space-y-4">
                            <label class="block">Tipo<select v-model="kind" class="mt-1 block w-full rounded border-gray-300"><option value="in">Entrada</option><option value="out">Salida</option></select></label>
                            <label class="block">Importe ($)<input v-model="amount" type="number" min="0.01" max="99999999.99" step="0.01" required class="mt-1 block w-full rounded border-gray-300"></label>
                            <label class="block">Motivo<input v-model="description" maxlength="255" required class="mt-1 block w-full rounded border-gray-300"></label>
                            <button class="rounded bg-green-700 px-4 py-2 text-white">Guardar movimiento</button>
                        </fieldset>
                    </form>
                    <form class="space-y-4 rounded-xl bg-white p-6 shadow" @submit.prevent="submit('close', { session_id: current.id, counted, expected: current.expected, notes, confirmed: true })">
                        <h3 class="text-lg font-semibold">Corte de caja</h3>
                        <fieldset :disabled="!available || busy || loading || !!pending" class="space-y-4">
                            <label class="block">Efectivo contado ($)<input v-model="counted" type="number" min="0" max="99999999.99" step="0.01" required class="mt-1 block w-full rounded border-gray-300"></label>
                            <p v-if="counted !== ''">Diferencia: <strong>{{ money(Number(counted) - Number(current.expected)) }}</strong></p>
                            <label class="block">Observaciones<textarea v-model="notes" maxlength="2000" :required="counted !== '' && Number(counted) !== Number(current.expected)" class="mt-1 block w-full rounded border-gray-300"></textarea></label>
                            <label class="flex items-start gap-2"><input type="checkbox" required class="mt-1 rounded">Confirmo el conteo y que sincronizamos las ventas de todos mis dispositivos.</label>
                            <button class="min-h-14 w-full rounded-xl bg-green-700 px-6 py-3 text-base font-bold text-white">Guardar corte y cerrar caja</button>
                        </fieldset>
                    </form>
                </div>
                <section class="overflow-x-auto rounded-xl bg-white p-6 shadow">
                    <h3 class="mb-3 text-lg font-semibold">Últimos 100 movimientos</h3>
                    <table class="w-full text-left text-sm"><thead><tr><th class="p-2">Fecha</th><th>Tipo</th><th>Motivo / referencia</th><th class="text-right">Importe</th></tr></thead>
                        <tbody><tr v-for="m in cash.movements" :key="m.id" class="border-t"><td class="p-2">{{ date(m.date) }}</td><td>{{ labels[m.kind] }}</td><td>{{ m.description }}</td><td class="text-right">{{ money(m.amount) }}</td></tr></tbody>
                    </table>
                    <p v-if="!cash.movements.length" class="py-3 text-gray-500">Sin movimientos todavía.</p>
                </section>
            </template>
            <section class="overflow-x-auto rounded-xl bg-white p-6 shadow">
                <h3 class="mb-3 text-lg font-semibold">Últimos 20 cortes</h3>
                <table class="w-full text-left text-sm"><thead><tr><th class="p-2">Cierre</th><th>Esperado</th><th>Contado</th><th>Diferencia</th><th></th></tr></thead>
                    <tbody><tr v-for="s in cash.history" :key="s.id" class="border-t"><td class="p-2">{{ date(s.closedAt) }}</td><td>{{ money(s.expected) }}</td><td>{{ money(s.counted) }}</td><td>{{ money(s.difference) }}</td><td><button class="font-semibold text-green-700 underline" @click="printCut(s)">Imprimir corte</button></td></tr></tbody>
                </table><p v-if="!cash.history.length" class="py-3 text-gray-500">Aún no hay cortes guardados.</p>
            </section>
        </div>
        <Teleport to="body"><div v-if="printable" class="cash-print">
            <h1>Corte de caja · AgroSys</h1><p>{{ page.props.auth.user.name }} · {{ page.props.sucursalActiva.nombre }}</p>
            <p>Folio: {{ printable.id }}</p><p>Apertura: {{ date(printable.openedAt) }}</p><p>Cierre: {{ date(printable.closedAt) }}</p>
            <p>Fondo inicial: {{ money(printable.opening) }}</p><p v-for="(value, key) in printable.totals" :key="key">{{ labels[key] }}: {{ money(value) }}</p>
            <p>Efectivo esperado: {{ money(printable.expected) }}</p><p>Efectivo contado: {{ money(printable.counted) }}</p><p>Diferencia: {{ money(printable.difference) }}</p><p>{{ printable.notes }}</p>
        </div></Teleport>
    </section>
</template>

<style>
.cash-print { display: none; }
@media print {
    body > * { display: none !important; }
    body > .cash-print { display: block !important; font: 12pt sans-serif; color: black; padding: 16px; }
    .cash-print h1 { font-size: 18pt; font-weight: bold; margin-bottom: 12px; }
    .cash-print p { margin-bottom: 8px; }
}
</style>
