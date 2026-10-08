<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import StatTile from '@/Components/Dashboard/StatTile.vue';
import ChartCard from '@/Components/Dashboard/ChartCard.vue';
import ColumnChart from '@/Components/Dashboard/ColumnChart.vue';
import BarList from '@/Components/Dashboard/BarList.vue';
import CoverageList from '@/Components/Dashboard/CoverageList.vue';
import { formatCurrency as fmtMoney, formatCurrencyCompact, formatNumber, percentChange } from '@/Components/Dashboard/format';
import { useConnectivityStore } from '@/stores/connectivity';
import { offlineProductRepository } from '@/Offline/repositories/OfflineProductRepository';
import { notify } from '@/utils/notify';

const props = defineProps({
    solucionesByProduct: {
        type: Object,
        default: {}
    },
    perfil: {
        type: String,
        default: 'vendedor'
    },
    metricas: {
        type: Object,
        default: null
    },
});

const page = usePage();
const q = ref('');
const connectivity = useConnectivityStore();
const localProducts = ref([]);
const localLoading = ref(false);
const isOfflineCatalog = computed(() => !connectivity.isUsableOnline);

const loadLocalProducts = async (query = '') => {
    localLoading.value = true;
    try {
        localProducts.value = await offlineProductRepository.search(query);
    } catch {
        localProducts.value = [];
        notify('El catálogo local aún no está disponible. Conéctate una vez para sincronizarlo.', 'error');
    } finally {
        localLoading.value = false;
    }
};

watch(q, (value) => {
    if (isOfflineCatalog.value) {
        loadLocalProducts(value);
        return;
    }
    router.get(route('dashboard', { q: value, periodo: periodo.value }), {}, { preserveState: true, preserveScroll: true, only: ['solucionesByProduct'] });
});

watch(() => connectivity.mode, mode => {
    if (mode !== 'online') loadLocalProducts(q.value);
});

onMounted(() => {
    if (isOfflineCatalog.value) loadLocalProducts();
});

const formatCurrency = value => Number(value ?? 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const formatStockDate = value => value ? new Date(value).toLocaleString('es-MX') : 'Sin sincronización';

// ---------------------------------------------------------------------------
// Métricas por perfil
// ---------------------------------------------------------------------------

const m = computed(() => props.metricas);
const r = computed(() => props.metricas?.resumen ?? {});
const nombre = computed(() => (page.props.auth?.user?.name ?? '').split(' ')[0]);
const sucursal = computed(() => page.props.sucursalActiva?.nombre ?? 'tu sucursal');
const esAdmin = computed(() => ['admin', 'empresa'].includes(props.perfil));

const saludo = computed(() => {
    const h = new Date().getHours();
    return h < 12 ? 'Buenos días' : h < 19 ? 'Buenas tardes' : 'Buenas noches';
});
const subtitulo = computed(() => ({
    vendedor: `Así va la caja hoy en ${sucursal.value}.`,
    inventario: `Existencias y rotación de ${sucursal.value}.`,
    admin: `Resumen de ${sucursal.value}.`,
    empresa: 'Resumen de todas las sucursales de la empresa.',
}[props.perfil]));

const periodos = [
    { value: 7, label: '7 días' },
    { value: 30, label: '30 días' },
    { value: 90, label: '90 días' },
];
const periodo = ref(props.metricas?.periodo ?? 30);
const cargando = ref(false);
const cambiarPeriodo = (valor) => {
    if (valor === periodo.value) return;
    periodo.value = valor;
    router.get(route('dashboard', { periodo: valor, q: q.value || undefined }), {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['metricas'],
        onStart: () => { cargando.value = true; },
        onFinish: () => { cargando.value = false; },
    });
};
const vsAnterior = computed(() => `vs. ${periodo.value} días anteriores`);

const series = [
    { key: 'contado', label: 'Contado', color: 'var(--series-1)' },
    { key: 'credito', label: 'Crédito', color: 'var(--series-2)' },
];
const fechaCorta = new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short' });
const fechaLarga = new Intl.DateTimeFormat('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
const aFecha = (iso) => new Date(`${iso}T12:00:00`);

const puntosSerie = computed(() => {
    const serie = m.value?.serie;
    if (!serie) return [];
    return serie.puntos.map((p) => ({
        label: fechaCorta.format(aFecha(p.fecha)).replace('.', ''),
        fullLabel: serie.agrupacion === 'semana' ? `Semana del ${fechaCorta.format(aFecha(p.fecha))}` : fechaLarga.format(aFecha(p.fecha)),
        values: { contado: p.contado, credito: p.credito },
        tickets: p.tickets,
    }));
});
const totalCredito = computed(() => (m.value?.serie?.puntos ?? []).reduce((t, p) => t + p.credito, 0));
const totalSerie = computed(() => (m.value?.serie?.puntos ?? []).reduce((t, p) => t + p.contado + p.credito, 0));

const horaLabel = (h) => `${h % 12 === 0 ? 12 : h % 12}${h < 12 ? 'am' : 'pm'}`;
const puntosHora = computed(() => (m.value?.ventasPorHora ?? []).map((p) => ({
    label: horaLabel(p.hora),
    fullLabel: `De ${horaLabel(p.hora)} a ${horaLabel(p.hora + 1)}`,
    values: { total: p.total },
    tickets: p.tickets,
})));
const serieHora = [{ key: 'total', label: 'Ventas', color: 'var(--series-1)' }];
const ticketsTexto = (p) => `${p.tickets} ${p.tickets === 1 ? 'venta' : 'ventas'}`;

const itemsProductos = (porUnidades = false) => (m.value?.topProductos ?? []).map((p) => ({
    label: p.nombre,
    sublabel: p.tamano,
    value: porUnidades ? p.unidades : p.importe,
    valueLabel: porUnidades ? `${formatNumber(p.unidades)} pzas` : formatCurrencyCompact(p.importe),
    detail: porUnidades ? `${fmtMoney(p.importe)} vendidos` : `${formatNumber(p.unidades)} piezas`,
}));
const itemsVendedores = computed(() => (m.value?.porVendedor ?? []).map((v) => ({
    label: v.nombre, value: v.total, valueLabel: formatCurrencyCompact(v.total), detail: `${v.tickets} ${v.tickets === 1 ? 'venta' : 'ventas'}`,
})));
const itemsSucursales = computed(() => (m.value?.porSucursal ?? []).map((s) => ({
    label: s.nombre, value: s.total, valueLabel: formatCurrencyCompact(s.total),
})));
const itemsAdeudo = computed(() => (m.value?.clientesAdeudo ?? []).map((c) => ({
    label: c.nombre, value: c.total, valueLabel: fmtMoney(c.total),
})));
</script>

<template>
    <AppLayout title="Inicio">
        <div class="dash-viz mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Encabezado y periodo -->
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ saludo }}{{ nombre ? `, ${nombre}` : '' }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ subtitulo }}</p>
                </div>
                <div v-if="m && perfil !== 'vendedor'" role="radiogroup" aria-label="Periodo" class="flex gap-1 rounded-xl bg-gray-200/70 p-1 dark:bg-gray-800">
                    <button v-for="p in periodos" :key="p.value" type="button" role="radio" :aria-checked="periodo === p.value" @click="cambiarPeriodo(p.value)"
                        class="h-10 rounded-lg px-4 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        :class="periodo === p.value ? 'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-white' : 'text-gray-700 hover:text-gray-900 dark:text-gray-300'">
                        {{ p.label }}
                    </button>
                </div>
            </div>

            <div v-if="!m || isOfflineCatalog" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                <p><strong>Las gráficas necesitan conexión.</strong> Abajo puedes seguir consultando el catálogo guardado en este dispositivo.</p>
            </div>

            <div v-if="m && !isOfflineCatalog" class="flex flex-col gap-6 transition-opacity" :class="cargando ? 'opacity-60' : ''">

                <!-- Vendedor -->
                <template v-if="perfil === 'vendedor'">
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <StatTile label="Mis ventas de hoy" :value="formatCurrencyCompact(r.misVentasHoy)" :hint="`${r.misTicketsHoy} ${r.misTicketsHoy === 1 ? 'ticket' : 'tickets'}`" />
                        <StatTile label="Mis tickets de hoy" :value="formatNumber(r.misTicketsHoy)" hint="Ventas que cobraste" />
                        <StatTile label="Ventas de la sucursal hoy" :value="formatCurrencyCompact(r.ventasSucursalHoy)" hint="Todas las cajas" />
                        <StatTile label="Ticket promedio de hoy" :value="formatCurrencyCompact(r.ticketPromedioHoy)" hint="En la sucursal" />
                    </div>
                    <div class="grid gap-6 lg:grid-cols-5">
                        <ChartCard class="lg:col-span-3" title="Ventas por hora" :subtitle="`Hoy en ${sucursal}`">
                            <ColumnChart :points="puntosHora" :series="serieHora" :format="fmtMoney" :format-tick="formatCurrencyCompact" :extra="ticketsTexto" aria-label="Ventas de hoy por hora" />
                        </ChartCard>
                        <ChartCard class="lg:col-span-2" title="Por agotarse" subtitle="Días de existencia al ritmo de venta actual">
                            <CoverageList :items="m.porAgotarse" />
                        </ChartCard>
                    </div>
                    <ChartCard title="Lo más vendido" subtitle="Últimos 7 días, por importe">
                        <BarList :items="itemsProductos()" empty="Aún no hay ventas esta semana." />
                    </ChartCard>
                </template>

                <!-- Inventario -->
                <template v-else-if="perfil === 'inventario'">
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <StatTile label="Agotados con demanda" :value="formatNumber(r.agotados)" hint="Se vendieron en los últimos 30 días" :tone="r.agotados > 0 ? 'critical' : 'neutral'" />
                        <StatTile label="Se agotan en menos de 7 días" :value="formatNumber(r.porAgotarse)" hint="Al ritmo de venta actual" :tone="r.porAgotarse > 0 ? 'serious' : 'neutral'" />
                        <StatTile label="Productos con existencia" :value="formatNumber(r.conExistencia)" :hint="`En ${sucursal}`" />
                        <StatTile label="Piezas vendidas" :value="formatNumber(r.unidadesVendidas)" :hint="`Últimos ${periodo} días`" />
                    </div>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <ChartCard title="Por agotarse" subtitle="Días de existencia según la venta diaria de los últimos 30 días">
                            <CoverageList :items="m.porAgotarse" />
                        </ChartCard>
                        <ChartCard title="Mayor rotación" :subtitle="`Piezas vendidas en los últimos ${periodo} días`">
                            <BarList :items="itemsProductos(true)" />
                        </ChartCard>
                    </div>
                </template>

                <!-- Administrador de sucursal / empresa -->
                <template v-else>
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <StatTile label="Ventas" :value="formatCurrencyCompact(r.ventas)" :delta="percentChange(r.ventas, r.ventasAnterior)" :delta-label="vsAnterior" />
                        <StatTile label="Tickets" :value="formatNumber(r.tickets)" :delta="percentChange(r.tickets, r.ticketsAnterior)" :delta-label="vsAnterior" />
                        <StatTile label="Ticket promedio" :value="formatCurrencyCompact(r.ticketPromedio)" :delta="percentChange(r.ticketPromedio, r.ticketPromedioAnterior)" :delta-label="vsAnterior" />
                        <StatTile label="Por cobrar" :value="formatCurrencyCompact(r.porCobrar)" :hint="`${r.clientesConAdeudo} ${r.clientesConAdeudo === 1 ? 'cliente' : 'clientes'} con saldo`" />
                    </div>

                    <ChartCard title="Ventas" :subtitle="`${m.serie.agrupacion === 'semana' ? 'Por semana' : 'Por día'} · ${totalSerie ? Math.round((totalCredito / totalSerie) * 100) : 0}% a crédito`">
                        <template #legend>
                            <ul class="flex items-center gap-4 text-sm text-gray-700 dark:text-gray-300">
                                <li v-for="s in series" :key="s.key" class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm" :style="{ background: s.color }"></span>{{ s.label }}</li>
                            </ul>
                        </template>
                        <ColumnChart :points="puntosSerie" :series="series" :format="fmtMoney" :format-tick="formatCurrencyCompact" :extra="ticketsTexto" :height="240"
                            :aria-label="`Ventas de contado y crédito de los últimos ${periodo} días`" />
                    </ChartCard>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <ChartCard v-if="perfil === 'empresa'" title="Ventas por sucursal" :subtitle="`Últimos ${periodo} días`">
                            <BarList :items="itemsSucursales" />
                        </ChartCard>
                        <ChartCard title="Ventas por vendedor" :subtitle="`Últimos ${periodo} días`">
                            <BarList :items="itemsVendedores" />
                        </ChartCard>
                        <ChartCard title="Productos más vendidos" :subtitle="`Últimos ${periodo} días, por importe`">
                            <BarList :items="itemsProductos()" />
                        </ChartCard>
                        <ChartCard title="Clientes con mayor saldo" subtitle="Cuentas por cobrar hoy">
                            <BarList :items="itemsAdeudo" empty="Ningún cliente tiene saldo pendiente." />
                        </ChartCard>
                        <ChartCard title="Por agotarse" :subtitle="`En ${sucursal}, al ritmo de venta actual`">
                            <CoverageList :items="m.porAgotarse" />
                        </ChartCard>
                    </div>
                </template>
            </div>

            <!-- Consulta de tratamientos -->
            <section class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Consulta de tratamientos</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Busca por producto, ingrediente activo, enfermedad o tipo de flor.</p>
                    </div>
                    <label class="relative block w-full sm:max-w-sm">
                        <span class="sr-only">Buscar tratamiento</span>
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                        <input v-model="q" type="search" autocomplete="off" placeholder="Buscar producto…"
                            class="h-12 w-full rounded-xl border-gray-300 pl-11 text-base focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </label>
                </div>
            <div v-if="isOfflineCatalog" class="mb-5 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                <p class="font-semibold">Catálogo local</p>
                <p>Los precios y existencias corresponden a la última sincronización. La existencia incluye movimientos pendientes de este dispositivo.</p>
            </div>

            <div v-if="isOfflineCatalog && localLoading" class="py-8 text-center text-sm text-gray-500">Consultando productos guardados…</div>

            <div v-if="isOfflineCatalog && !localLoading" class="grid grid-cols-1 gap-4 md:hidden">
                <article v-for="product in localProducts" :key="product.id" class="rounded-lg border bg-white p-4 shadow-sm">
                    <div class="font-semibold text-gray-900">{{ product.name }} <span v-if="product.size">- {{ product.size }}</span></div>
                    <div class="mt-1 text-xs text-gray-500">Código: {{ product.barcode || product.sku || 'N/D' }}</div>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div><div class="text-gray-500">Precio</div><strong>{{ formatCurrency(product.price) }}</strong></div>
                        <div><div class="text-gray-500">Existencia estimada</div><strong>{{ product.stock?.estimatedQuantity ?? 0 }}</strong></div>
                        <div><div class="text-gray-500">Existencia central</div><span>{{ product.stock?.serverQuantity ?? 0 }}</span></div>
                        <div><div class="text-gray-500">Movimientos locales</div><span>{{ product.stock?.localPendingDelta ?? 0 }}</span></div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500">Sincronizado: {{ formatStockDate(product.stock?.syncedAt) }}</div>
                </article>
                <p v-if="!localProducts.length" class="py-8 text-center text-gray-500">No se encontraron productos en el catálogo local.</p>
            </div>

            <div v-if="isOfflineCatalog && !localLoading" class="relative hidden overflow-x-auto md:block">
                <table class="w-full table-auto text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase"><tr class="[&>th]:px-4 [&>th]:py-3"><th>Producto</th><th>Código</th><th>Precio</th><th>Existencia central</th><th>Movimientos locales</th><th>Existencia estimada</th><th>Sincronización</th></tr></thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-3">
                        <tr v-for="product in localProducts" :key="product.id" class="border-b bg-white">
                            <td class="font-medium text-gray-900">{{ product.name }} <span v-if="product.size">- {{ product.size }}</span></td>
                            <td>{{ product.barcode || product.sku || 'N/D' }}</td>
                            <td>{{ formatCurrency(product.price) }}</td>
                            <td>{{ product.stock?.serverQuantity ?? 0 }}</td>
                            <td>{{ product.stock?.localPendingDelta ?? 0 }}</td>
                            <td class="font-semibold">{{ product.stock?.estimatedQuantity ?? 0 }}</td>
                            <td>{{ formatStockDate(product.stock?.syncedAt) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!localProducts.length" class="py-8 text-center text-gray-500">No se encontraron productos en el catálogo local.</p>
            </div>

            <!-- Vista en tarjetas (mobile) -->
            <div v-if="!isOfflineCatalog" class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4  w-full">
                <div v-for="s in solucionesByProduct.data" :key="s.producto.id"
                    class="rounded-lg border p-4 bg-white shadow-sm">
                    <div class="text-sm text-gray-500">Nombre del producto</div>
                    <div class="font-semibold text-gray-900">{{ s.producto.nombre }}</div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">Enfermedad</div>
                            <div>{{ s.enfermedad.nombre }} en {{ s.tipoFlor.nombre }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Ingrediente activo</div>
                            <div>{{ s.producto.ingrediente_activo }}</div>
                        </div>
                        <div class="col-span-2">
                            <div class="text-gray-500">Condiciones</div>
                            <div class="line-clamp-3">{{ s.condiciones }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Dosis (ml/bomba)</div>
                            <div>{{ s.dosis_bomba_ml }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Dosis (ml/tambo)</div>
                            <div>{{ s.dosis_tambo_ml }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Stock</div>
                            <div>{{ s.producto.cantidad }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Actualización</div>
                            <div class="whitespace-nowrap">{{ s.producto.updated_at }}</div>
                        </div>
                    </div>
                </div>

                <!-- Paginación -->
                <div name="Pagination">
                    <Pagination class="mt-4" :links="solucionesByProduct.links" :prefix="''" />
                </div>
            </div>

            <!-- Vista en tabla (md y arriba) -->
            <div v-if="!isOfflineCatalog" class="relative overflow-x-auto hidden md:block">
                <table class="w-full table-auto text-sm text-left text-gray-600">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Nombre del producto</th>
                            <th>Enfermedad en planta</th>
                            <th>Ingrediente activo</th>
                            <th>Condiciones de aplicación</th>
                            <th>Dosis (ml/bomba)</th>
                            <th>Dosis (ml/tambo)</th>
                            <th>Stock</th>
                            <th>Última actualización</th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-2">
                        <tr v-for="s in solucionesByProduct.data" :key="s.producto.id" class="border-b">
                            <td class="font-medium text-gray-900">{{ s.producto.nombre }}</td>
                            <td>{{ s.enfermedad.nombre }} en {{ s.tipoFlor.nombre }}</td>
                            <td>{{ s.producto.ingrediente_activo }}</td>
                            <td class="max-w-[28ch] truncate" title="{{ s.condiciones }}">{{ s.condiciones }}</td>
                            <td class="whitespace-nowrap">{{ s.dosis_bomba_ml }}</td>
                            <td class="whitespace-nowrap">{{ s.dosis_tambo_ml }}</td>
                            <td class="whitespace-nowrap">{{ s.producto.cantidad }}</td>
                            <td class="whitespace-nowrap">{{ s.producto.updated_at }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginación -->
                <div name="Pagination">
                    <Pagination class="mt-6" :links="solucionesByProduct.links" :prefix="''" />
                </div>
            </div>

            </section>
        </div>
    </AppLayout>
</template>

<style>
.dash-viz {
    --series-1: #2a78d6;
    --series-2: #eb6834;
    --status-critical: #d03b3b;
    --status-serious: #ec835a;
    --status-warning: #fab219;
    --viz-grid: #e5e7eb;
    --viz-baseline: #9ca3af;
    --viz-muted: #6b7280;
    --viz-hover: rgba(17, 24, 39, 0.05);
}
@media (prefers-color-scheme: dark) {
    .dash-viz {
        --series-1: #3987e5;
        --series-2: #d95926;
        --viz-grid: #374151;
        --viz-baseline: #6b7280;
        --viz-muted: #9ca3af;
        --viz-hover: rgba(255, 255, 255, 0.06);
    }
}
</style>
