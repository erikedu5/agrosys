<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { formatFecha, formatMoneda } from '@/utils/formato';
import { statusBadge, cantidad, diferenciaClase } from '@/utils/auditoria';
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    filtros: { type: Object, default: () => ({}) },
    empresas: { type: Array, default: () => [] },
    sucursales: { type: Array, default: () => [] },
    marcas: { type: Array, default: () => [] },
    resultados: { type: Array, default: null },
    verCostos: { type: Boolean, default: false },
});

const f = reactive({
    empresa: props.filtros.empresa ?? (props.empresas.length === 1 ? props.empresas[0].id : ''),
    sucursal: props.filtros.sucursal ?? '',
    desde: props.filtros.desde ?? '',
    hasta: props.filtros.hasta ?? '',
    marca: props.filtros.marca ?? '',
    sku: props.filtros.sku ?? '',
});

const sucursalesFiltradas = computed(() => props.sucursales.filter(s => !f.empresa || Number(s.id_empresa) === Number(f.empresa)));
const params = () => Object.fromEntries(Object.entries(f).filter(([, v]) => v !== '' && v !== null));
const buscar = () => router.get(route('auditorias.historico'), { ...params(), buscar: 1 }, { preserveState: true, replace: true });
const urlExcel = computed(() => route('auditorias.historico', { ...params(), formato: 'xlsx' }));
const porRenglon = computed(() => !!(props.filtros.marca || props.filtros.sku));
</script>

<template>
    <AppLayout title="Histórico de auditorías">
        <template #header>
            <PageHeader title="Histórico de auditorías" subtitle="Por sucursal o bodega, marca, SKU y fecha" :back="route('auditorias.index')" />
        </template>

        <div class="ui-page">
            <form class="ui-card grid grid-cols-1 gap-3 p-4 sm:grid-cols-3 lg:grid-cols-6" @submit.prevent="buscar">
                <div v-if="empresas.length > 1" class="ui-field">
                    <label class="ui-label" for="h-empresa">Empresa</label>
                    <select id="h-empresa" v-model="f.empresa" class="ui-input" @change="f.sucursal = ''">
                        <option value="">Todas</option>
                        <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                    </select>
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="h-sucursal">Sucursal / bodega</label>
                    <select id="h-sucursal" v-model="f.sucursal" class="ui-input">
                        <option value="">Todas</option>
                        <option v-for="s in sucursalesFiltradas" :key="s.id" :value="s.id">{{ s.nombre }}{{ s.es_bodega ? ' (bodega)' : '' }}</option>
                    </select>
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="h-desde">Desde</label>
                    <input id="h-desde" v-model="f.desde" type="date" class="ui-input">
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="h-hasta">Hasta</label>
                    <input id="h-hasta" v-model="f.hasta" type="date" class="ui-input">
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="h-marca">Marca</label>
                    <select id="h-marca" v-model="f.marca" class="ui-input">
                        <option value="">Todas</option>
                        <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="h-sku">SKU / código</label>
                    <input id="h-sku" v-model="f.sku" type="search" class="ui-input" placeholder="Código o ID">
                </div>
                <div class="flex gap-2 sm:col-span-3 lg:col-span-6 sm:justify-end">
                    <a v-if="resultados && resultados.length" :href="urlExcel" class="ui-btn flex-1 sm:flex-none">Excel</a>
                    <button type="submit" class="ui-btn ui-btn-primary flex-1 sm:flex-none">Buscar</button>
                </div>
            </form>

            <EmptyState v-if="resultados === null" title="Elige filtros y busca" text="Se muestran auditorías en revisión o finalizadas." />
            <EmptyState v-else-if="!resultados.length" title="Sin auditorías para esos filtros" />

            <section v-for="a in resultados || []" :key="a.id" class="ui-card overflow-hidden">
                <header class="flex flex-wrap items-center gap-2 px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">{{ a.folio }} · {{ formatFecha(a.fecha, true) }}</p>
                        <p class="font-bold">{{ a.es_bodega ? 'Bodega' : 'Sucursal' }} {{ a.sucursal }} <span class="ui-muted font-normal">· {{ a.empresa }}</span></p>
                    </div>
                    <span :class="statusBadge(a.status)">{{ a.status_label }}</span>
                    <Link :href="route('auditorias.show', a.id)" class="ui-btn">Ver</Link>
                </header>
                <div class="grid grid-cols-2 gap-3 border-t border-gray-100 px-4 py-3 text-sm dark:border-gray-700 sm:grid-cols-5">
                    <div><p class="ui-help">Renglones</p><p class="font-semibold">{{ a.totales.renglones }}</p></div>
                    <div><p class="ui-help">Exactitud</p><p class="font-semibold">{{ a.totales.exactitud ?? '—' }}%</p></div>
                    <div><p class="ui-help">Faltantes</p><p class="font-semibold text-red-700 dark:text-red-300">{{ cantidad(a.totales.unidades_faltantes) }}</p></div>
                    <div><p class="ui-help">Sobrantes</p><p class="font-semibold text-blue-700 dark:text-blue-300">{{ cantidad(a.totales.unidades_sobrantes) }}</p></div>
                    <div v-if="verCostos"><p class="ui-help">Valor neto</p><p class="font-semibold" :class="diferenciaClase(a.totales.valor_neto)">{{ formatMoneda(a.totales.valor_neto) }}</p></div>
                </div>
                <div v-if="porRenglon && a.renglones.length" class="overflow-x-auto border-t border-gray-100 dark:border-gray-700">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600 dark:bg-gray-900 dark:text-gray-400">
                            <tr><th class="px-3 py-2">Producto</th><th class="px-3 py-2">Marca</th><th class="px-3 py-2 text-right">Esperado</th><th class="px-3 py-2 text-right">Contado</th><th class="px-3 py-2 text-right">Diferencia</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in a.renglones" :key="r.id" class="border-t border-gray-100 dark:border-gray-700">
                                <td class="px-3 py-2"><span class="font-semibold">{{ r.nombre }}</span> <span class="ui-help">SKU {{ r.sku }}</span></td>
                                <td class="px-3 py-2">{{ r.marca }}</td>
                                <td class="px-3 py-2 text-right">{{ cantidad(r.esperado) }}</td>
                                <td class="px-3 py-2 text-right">{{ cantidad(r.contado) }}</td>
                                <td class="px-3 py-2 text-right" :class="diferenciaClase(r.diferencia)">{{ cantidad(r.diferencia) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
