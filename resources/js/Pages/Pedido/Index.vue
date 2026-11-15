<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchField from '@/Components/SearchField.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ref, watch, computed } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import PaginationBar from '@/Components/PaginationBar.vue';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';

const props = defineProps({
    pedidos: Object,
    sucursales: Array,
    filtroSucursal: { type: [Number, String], default: null },
    filtroCompletado: { type: [Number, String], default: '' },
    search: { type: String, default: '' },
});

const q = ref(props.search);
const sucursal = ref(props.filtroSucursal ?? '');
const estado = ref(props.filtroCompletado ?? '');
const estadoOptions = [
    { value: '', label: 'Todos' },
    { value: '0', label: 'Pendiente' },
    { value: '1', label: 'Completado' }
];
const estadoSeleccionado = ref(estadoOptions.find(o => o.value === estado.value));

watch(q, (v) => {
    router.get(route('pedidos.index', { q: v, completado: estado.value }), {}, { preserveState: true });
});

watch(estadoSeleccionado, (v) => {
    estado.value = v ? v.value : '';
    router.get(route('pedidos.index', { completado: estado.value, q: q.value }), {}, { preserveState: true });
});

const groupedPedidos = computed(() => {
    const groups = [];
    let current = null;

    props.pedidos.data.forEach(p => {
        if (!current || current.uuid !== p.pedido_uuid) {
            current = { uuid: p.pedido_uuid, items: [] };
            groups.push(current);
        }
        current.items.push(p);
    });

    return groups;
});

const nombreProducto = (producto) => {
    if (!producto?.nombre) {
        return 'Producto (BORRADO)';
    }
    return producto.deleted_at ? `${producto.nombre} (BORRADO)` : producto.nombre;
};

const completar = (id) => {
    if (confirm('¿Marcar como completado?')) {
        useForm({}).put(route('pedidos.complete', id));
    }
};
</script>

<template>
    <AppLayout title="Pedidos">
        <template #header>
            <PageHeader title="Pedidos" subtitle="Solicitudes de producto por sucursal" />
        </template>

        <div class="ui-page">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <SearchField v-model="q" placeholder="Buscar pedido" class="flex-1" />
                <vue-single-select v-model="estadoSeleccionado" :options="estadoOptions" option-key="value" option-label="label" placeholder="Todos" class="w-full sm:w-56" />
            </div>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <section v-for="g in groupedPedidos" :key="g.uuid" class="ui-card overflow-hidden">
                    <header class="flex items-start justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Pedido {{ g.uuid }}</p>
                            <h2 class="text-[17px] font-bold leading-snug">{{ g.items[0].nombre_solicitante }}</h2>
                            <p class="ui-muted">{{ g.items[0].numero_solicitante }} · Sucursal {{ g.items[0].sucursal?.nombre }}</p>
                        </div>
                        <span class="ui-badge shrink-0">{{ g.items.length === 1 ? '1 producto' : `${g.items.length} productos` }}</span>
                    </header>
                    <div v-for="p in g.items" :key="p.id" class="ui-row flex-wrap">
                        <span class="min-w-0 flex-1 font-semibold">{{ nombreProducto(p.producto) }}</span>
                        <span class="ui-badge">{{ p.cantidad }} uds.</span>
                        <span class="ui-badge" :class="p.completado ? 'ui-badge-green' : 'ui-badge-amber'">{{ p.completado ? 'Completado' : 'Pendiente' }}</span>
                        <button v-if="!p.completado" type="button" class="ui-btn" @click="completar(p.id)">Completar</button>
                    </div>
                </section>
            </div>

            <EmptyState v-if="!groupedPedidos.length" title="No hay pedidos" text="No hay pedidos con los filtros seleccionados." />
            <PaginationBar :paginator="pedidos" :prefix="''" />
        </div>
    </AppLayout>
</template>
