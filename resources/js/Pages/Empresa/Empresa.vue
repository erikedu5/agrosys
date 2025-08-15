<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch } from 'vue';
    import { router, Link, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue'

    const props = defineProps({
        empresas: {
            type: Array,
            default: []
        },
        showDeleted: {
            type: Boolean,
            default: false
        }
    });

    const q = ref('');
    const showDeleted = ref(props.showDeleted);

    watch(q, (value) => {
        router.get(route('empresa.index', { q: value, deleted: showDeleted.value }), {}, { preserveState: true });
    });

    const toggleDeleted = () => {
        showDeleted.value = !showDeleted.value;
        router.get(route('empresa.index', { q: q.value, deleted: showDeleted.value }), {}, { preserveState: true });
    };

    const desactivar = (id) => {
        if (confirm("¿Desea desactivar la empresa?")) {
            useForm({}).delete(route('empresa.destroy', id));
        }
    };

    const restaurar = (id) => {
        if (confirm("¿Desea restaurar la empresa?")) {
            useForm({}).put(route('empresa.restore', id));
        }
    };
</script>

<template>
    <AppLayout title="Empresa">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Empresas
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">

                        <div class="flex justify-between">
                            <input type="text" class="form-input rounded-md shadow-sm w-5/6" v-model="q" placeholder="Buscar empresa...">
                            <div class="flex space-x-2">
                                <Link :href="route('empresa.create')"
                                      class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                           hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                           focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                           dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Crear empresa
                                </Link>
                                <button @click="toggleDeleted"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                               hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                               focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                               dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    {{ showDeleted ? 'Ver activos' : 'Ver eliminados' }}
                                </button>
                            </div>
                        </div>

                        <hr class="my-6">

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                    <th>Id</th>
                                    <th>Nombre de la empresa</th>
                                    <th>Dirección</th>
                                    <th>Telefono</th>
                                    <th>Email</th>
                                    <th>RFC</th>
                                    <th>Aviso</th>
                                    <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="empresa in empresas.data" :key="empresa.id">
                                        <td class="px-4 py-2"> {{ empresa.id }}</td>
                                        <td class="px-4 py-2"> {{ empresa.nombre }} </td>
                                        <td class="px-4 py-2"> {{ empresa.direccion }} </td>
                                        <td class="px-4 py-2"> {{ empresa.telefono }} </td>
                                        <td class="px-4 py-2"> {{ empresa.email }} </td>
                                        <td class="px-4 py-2"> {{ empresa.rfc }} </td>
                                        <td class="px-4 py-2"> {{ empresa.aviso }} </td>
                                        <td class="px-4 py-2">
                                            <div v-if="!showDeleted" class="inline-flex rounded-md shadow-sm" role="group">
                                                <Link :href="route('empresa.edit', empresa.id)"
                                                      class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Actualizar
                                                </Link>
                                                <Link href="" @click.prevent="desactivar(empresa.id)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md  hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Desactivar
                                                </Link>
                                            </div>
                                            <div v-else>
                                                <Link href="" @click.prevent="restaurar(empresa.id)"
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
                            <Pagination class="mt-6" :links="empresas.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
