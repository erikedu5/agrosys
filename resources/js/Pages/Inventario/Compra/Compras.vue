<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'

defineProps({
    compras: {
        type: Object,
        default: {}
    }
});

const q = ref('');

const statusClass = (status) => {
    switch (status) {
        case 'pagada':
            return 'bg-green-100 text-green-800';
        case 'adeudo':
            return 'bg-yellow-100 text-yellow-800';
        case 'retrasada':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

watch(q, (value) => {
    router.get(route('compra.index', { q: value }), {}, { preserveState: true });
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Compras a proveedores
            </h2>
            <br>
            <div class="flex justify-between">
                <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                    placeholder="Buscar compra...">

            </div>
        </template>

        <hr class="my-6">
        <div class="flex justify-end max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-2">
            <Link :href="route('compra.create')"
                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
            Nueva compra
            </Link>
        </div>
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-2">

            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 gap-4 w-full">
                <div v-for="compra in compras.data" :key="compra.id" class="shadow bg-white border border-gray-200 rounded-md p-4 dark:bg-gray-800 dark:border-gray-700">
                    <div class="text-sm text-gray-500">Proveedor</div>
                    <div class="font-semibold text-gray-900">{{ compra.proveedor }}</div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">Fecha de compra</div>
                            <div>{{ compra.fecha_compra }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Total de la compra</div>
                            <div>{{ compra.total_compra }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Fecha limite de credito</div>
                            <div>{{ compra.fecha_credito }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Total del credito</div>
                            <div>{{ compra.total_credito }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Estatus de la compra</div>
                            <span :class="'px-2 py-1 rounded-full text-xs capitalize ' + statusClass(compra.status)">{{ compra.status }}</span>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end">
                         <div class="inline-flex rounded-md shadow-sm" role="group">
                                    <Link :href="route('compra.show', compra.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Detalle
                                    </Link>
                                </div>
                    </div>
                </div>
                <div name="Pagination">
                    <Pagination class="mt-6" :links="compras.links" />
                </div>
            </div>
            <div class="relative overflow-x-auto hidden md:block w-full shadow bg-white md:rounded-md p-4 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id</th>
                            <th>Proveedor</th>
                            <th>Fecha de compra</th>
                            <th>Total de la compra</th>
                            <th>Fecha limite de credito</th>
                            <th>Total del credito</th>
                            <th>Estatus de la compra</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-2">
                        <tr v-for="compra in compras.data" :key="compra.id" class="border-b dark:border-gray-700">
                            <td class="px-4 py-2"> {{ compra.id }}</td>
                            <td class="px-4 py-2"> {{ compra.proveedor }} </td>
                            <td class="px-4 py-2"> {{ compra.fecha_compra }} </td>
                            <td class="px-4 py-2"> {{ compra.total_compra }}</td>
                            <td class="px-4 py-2"> {{ compra.fecha_credito }} </td>
                            <td class="px-4 py-2"> {{ compra.total_credito }}</td>
                            <td class="px-4 py-2">
                                <span :class="'px-2 py-1 rounded-full text-xs capitalize ' + statusClass(compra.status)">{{ compra.status }}</span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="inline-flex rounded-md shadow-sm" role="group">
                                    <Link :href="route('compra.show', compra.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Detalle
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div name="Pagination">
                    <Pagination class="mt-6" :links="compras.links" />
                </div>
            </div>


        </div>
    </AppLayout>
</template>
