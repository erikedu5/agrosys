<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch } from 'vue';
    import { router, Link, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue'
    const props = defineProps({
        sucursales: {
            type: Array,
            default: []
        },
        conteo: Number,
        empresa: Object,
        showDeleted: {
            type: Boolean,
            default: false
        }
    });

    const q = ref('');
    const showDeleted = ref(props.showDeleted);

    watch(q, (value) => {
        router.get(route('sucursal.index', { q: value, deleted: showDeleted.value }), {}, { preserveState: true });
    });

    const toggleDeleted = () => {
        showDeleted.value = !showDeleted.value;
        router.get(route('sucursal.index', { q: q.value, deleted: showDeleted.value }), {}, { preserveState: true });
    };

    const desactivar = (id) => {
        if (confirm("¿Desea desactivar la sucursal?")) {
            useForm({}).delete(route('sucursal.destroy', id));
        }
    };

    const restaurar = (id) => {
        if (confirm("¿Desea restaurar la sucursal?")) {
            useForm({}).put(route('sucursal.restore', id));
        }
    };
</script>

<template>
    <AppLayout title="Sucursal">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sucursales
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">

                        <div class="flex justify-between">
                            <input type="text" class="form-input rounded-md shadow-sm w-5/6" v-model="q" placeholder="Buscar sucursal...">
                            <div class="flex space-x-2">
                                <Link :href="route('sucursal.create')" v-if="conteo < empresa.numero_sucursales"
                                      class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                           hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                           focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                           dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Crear sucursal
                                </Link>
                                <button @click="toggleDeleted"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                               hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                               focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                               dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    {{ showDeleted ? 'Ver activas' : 'Ver eliminadas' }}
                                </button>
                            </div>
                        </div>

                        <hr class="my-6">

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                    <th>Id</th>
                                    <th>Nombre de la sucursal</th>
                                    <th>Dirección</th>
                                    <th>Telefono</th>
                                    <th>Email</th>
                                    <th>Es Matriz</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="sucursal in sucursales.data" :key="sucursal.id">
                                        <td class="px-4 py-2"> {{ sucursal.id }}</td>
                                        <td class="px-4 py-2"> {{ sucursal.nombre }} </td>
                                        <td class="px-4 py-2"> {{ sucursal.direccion }} </td>
                                        <td class="px-4 py-2"> {{ sucursal.telefono }} </td>
                                        <td class="px-4 py-2"> {{ sucursal.email }} </td>
                                        <td class="px-4 py-2"> {{ sucursal.es_matriz == 1? 'Si': 'No' }} </td>
                                        <td class="px-4 py-2">
                                            <div v-if="!showDeleted" class="inline-flex rounded-md shadow-sm" role="group">
                                                <Link :href="route('sucursal.edit', sucursal.id)"
                                                      class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Actualizar
                                                </Link>
                                                <Link href="" @click.prevent="desactivar(sucursal.id)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md  hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Desactivar
                                                </Link>
                                            </div>
                                            <div v-else>
                                                <Link href="" @click.prevent="restaurar(sucursal.id)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Restaurar
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div name="Pagination">
                            <Pagination class="mt-6" :links="sucursales.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
