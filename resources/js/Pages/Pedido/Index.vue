<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, computed } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
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

const completar = (id) => {
    if (confirm('¿Marcar como completado?')) {
        useForm({}).put(route('pedidos.complete', id));
    }
};
</script>

<template>
    <AppLayout title="Pedidos">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Pedidos
            </h2>
            <br>
            <input type="text" v-model="q" class="form-input rounded-md shadow-sm w-full" placeholder="Buscar..." />
        </template>

        <hr class="my-6" />
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 justify-end">
            <vue-single-select v-model="estadoSeleccionado" :options="estadoOptions" option-key="value" option-label="label" placeholder="Todos" class="w-48" />
        </div>
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">
            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <template v-for="g in groupedPedidos" :key="g.uuid">
                    <div class="bg-gray-100">
                        <td colspan="7" class="px-4 py-2 font-semibold">
                            Pedido {{ g.uuid }} - Sucursal: {{ g.items[0].sucursal.nombre }} -
                            Solicitante: {{ g.items[0].nombre_solicitante }} ({{
                                g.items[0].numero_solicitante }})
                        </td>
                    </div>
                    <div v-for="p in g.items" :key="p.id">
                        <div class="text-sm text-gray-500">Producto</div>
                        <div class="font-semibold text-gray-900">{{ p.producto.nombre }}</div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <div class="text-gray-500">Cantidad</div>
                                <div>{{ p.cantidad }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">Estado</div>
                                <div>
                                    <span v-if="p.completado" class="text-green-600">Completado</span>
                                    <span v-else class="text-yellow-600">Pendiente</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end mt-3">
                            <button v-if="!p.completado" @click="completar(p.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Completar
                            </button>
                        </div>
                    </div>
                </template>
                <Pagination class="mt-6" :links="pedidos.links" :prefix="''" />
            </div>
            <!-- Vista en tabla (md y arriba) -->
            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full table-auto text-sm text-left text-gray-600">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th class="px-4 py-2">Producto</th>
                            <th class="px-4 py-2">Cantidad</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-2">
                        <template v-for="g in groupedPedidos" :key="g.uuid">
                            <tr class="bg-gray-100">
                                <td colspan="7" class="px-4 py-2 font-semibold">
                                    Pedido {{ g.uuid }} - Sucursal: {{ g.items[0].sucursal.nombre }} -
                                    Solicitante: {{ g.items[0].nombre_solicitante }} ({{
                                        g.items[0].numero_solicitante }})
                                </td>
                            </tr>
                            <tr v-for="p in g.items" :key="p.id" class="border-b">
                                <td class="px-4 py-2">{{ p.producto.nombre }}</td>
                                <td class="px-4 py-2">{{ p.cantidad }}</td>
                                <td class="px-4 py-2">
                                    <span v-if="p.completado" class="text-green-600">Completado</span>
                                    <span v-else class="text-yellow-600">Pendiente</span>
                                </td>
                                <td class="px-4 py-2">
                                    <button v-if="!p.completado" @click="completar(p.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                        Completar
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <Pagination class="mt-6" :links="pedidos.links" :prefix="''" />
            </div>
        </div>
    </AppLayout>
</template>
