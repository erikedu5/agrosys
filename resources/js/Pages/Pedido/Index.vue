<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

defineProps({
    pedidos: Object,
    sucursales: Array,
    filtroSucursal: { type: [Number, String], default: null },
    filtroCompletado: { type: [Number, String], default: '' },
    search: { type: String, default: '' },
});

const q = ref(search);
const sucursal = ref(filtroSucursal ?? '');
const estado = ref(filtroCompletado ?? '');

watch(q, (v) => {
    router.get(route('pedidos.index', { q: v, sucursal: sucursal.value, completado: estado.value }), {}, { preserveState: true });
});
watch(sucursal, (v) => {
    router.get(route('pedidos.index', { sucursal: v, q: q.value, completado: estado.value }), {}, { preserveState: true });
});
watch(estado, (v) => {
    router.get(route('pedidos.index', { completado: v, sucursal: sucursal.value, q: q.value }), {}, { preserveState: true });
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
        </template>

        <hr class="my-6" />

        <div class="flex">
            <div class="flex-none w-14 h-14"></div>
            <div class="grow h-14">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <div class="flex justify-between mb-4 gap-2">
                            <input type="text" v-model="q" class="form-input rounded-md shadow-sm w-1/3" placeholder="Buscar..." />
                            <select v-model="sucursal" class="form-select">
                                <option value="">Todas las sucursales</option>
                                <option v-for="s in sucursales" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                            </select>
                            <select v-model="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="0">Pendiente</option>
                                <option value="1">Completado</option>
                            </select>
                        </div>

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-2">Sucursal</th>
                                        <th class="px-4 py-2">Producto</th>
                                        <th class="px-4 py-2">Cantidad</th>
                                        <th class="px-4 py-2">Nombre</th>
                                        <th class="px-4 py-2">Número</th>
                                        <th class="px-4 py-2">Estado</th>
                                        <th class="px-4 py-2">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in pedidos.data" :key="p.id">
                                        <td class="px-4 py-2">{{ p.sucursal.nombre }}</td>
                                        <td class="px-4 py-2">{{ p.producto.nombre }}</td>
                                        <td class="px-4 py-2">{{ p.cantidad }}</td>
                                        <td class="px-4 py-2">{{ p.nombre_solicitante }}</td>
                                        <td class="px-4 py-2">{{ p.numero_solicitante }}</td>
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
                                </tbody>
                            </table>
                        </div>

                        <Pagination class="mt-6" :links="pedidos.links" />
                    </div>
                </div>
            </div>
            <div class="flex-none w-14 h-14"></div>
        </div>
    </AppLayout>
</template>
