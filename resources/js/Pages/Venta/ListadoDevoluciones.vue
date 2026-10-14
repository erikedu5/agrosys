<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    devoluciones: { type: Array, default: [] }
});

const nombreProducto = (producto) => {
    if (producto?.nombre) {
        return producto.deleted_at ? `${producto.nombre} (BORRADO)` : producto.nombre;
    }
    return 'Producto (BORRADO)';
};
</script>
<template>
    <AppLayout title="Devoluciones">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Devoluciones
            </h2>
        </template>

        <hr class="my-6">
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 justify-end">
            <Link :href="route('venta.devoluciones.index')"
                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                        hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                        focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                        dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
            Crear una devolución
            </Link>
        </div>
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">
            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="d in devoluciones" :key="d.id" class="rounded-lg boder p-4 bg-white shadow-lg">
                    <div class="text-sm text-gray-500">Venta</div>
                    <div class="font-semibold text-gray-900">{{ d.venta_id }}</div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">Total devuelto</div>
                            <div>$ {{ d.total }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Fecha</div>
                            <div>{{ d.fecha }}</div>
                        </div>
                    </div>
                    <div class="flex justify-end mt-3">
                            <div class="text-gray-500">Productos</div>
                        <ul>
                            <li v-for="detalle in d.detalles" :key="detalle.id">
                                {{ nombreProducto(detalle.producto) }} - {{ detalle.cantidad }} unidades
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th class="px-2 py-1">ID</th>
                            <th class="px-2 py-1">Venta</th>
                            <th class="px-2 py-1">Total devuelto</th>
                            <th class="px-2 py-1">Fecha</th>
                            <th class="px-2 py-1">Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in devoluciones" :key="d.id" class="border-t">
                            <td class="px-2 py-1">{{ d.id }}</td>
                            <td class="px-2 py-1">{{ d.venta_id }}</td>
                            <td class="px-2 py-1">${{ d.total }}</td>
                            <td class="px-2 py-1">{{ d.fecha }}</td>
                            <td class="px-2 py-1">
                                <ul>
                                    <li v-for="detalle in d.detalles" :key="detalle.id">
                                        {{ nombreProducto(detalle.producto) }} - {{ detalle.cantidad }} unidades
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div name="Pagination">

            </div>
        </div>
    </AppLayout>
</template>
