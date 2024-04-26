<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch } from 'vue';
    import { router, Link, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue'

    defineProps({
        compras: {
            type: Array,
            default: []
        }
    });

    const q = ref('');

    watch(q, (value) => {
        router.get(route('compra.index', { q: value } ), {}, { preserveState: true });
    });
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Compras a proveedores
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">

                        <div class="flex justify-between">
                            <input type="text" class="form-input rounded-md shadow-sm w-5/6" v-model="q" placeholder="Buscar compra...">
                            <Link :href="route('compra.create')"
                                  class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Nueva compra
                            </Link>
                        </div>

                        <hr class="my-6">

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
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
                                <tbody>
                                    <tr v-for="compra in compras.data" :key="compra.id">
                                        <td class="px-4 py-2"> {{ compra.id }}</td>
                                        <td class="px-4 py-2"> {{ compra.proveedor }} </td>
                                        <td class="px-4 py-2"> {{ compra.fecha_compra }} </td>
                                        <td class="px-4 py-2"> {{ compra.total_compra }}</td>
                                        <td class="px-4 py-2"> {{ compra.fecha_credito }} </td>
                                        <td class="px-4 py-2"> {{ compra.total_credito }}</td>
                                        <td class="px-4 py-2"> {{ compra.status }}</td>
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
                        </div>

                        <div name="Pagination">
                            <Pagination class="mt-6" :links="compras.links" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-none w-14 h-14">
            </div>
        </div>
    </AppLayout>
</template>
