<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch } from 'vue';
    import { router, Link, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue'

    defineProps({
        enfermedades: {
            type: Array,
            default: []
        }
    })

    const q = ref('');

    watch(q, (value) => {
        router.get( route( 'enfermedad.index', { q: value } ), {}, { preserveState: true } );
    });

    const destroy = (id) => {
        if (confirm('¿Desea eliminar?')) {
            useForm({}).delete(route('enfermedad.destroy', id));
        }
    }
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Catalogo de Enfermedades
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">

                        <div class="flex justify-between">
                            <input type="text" class="form-input rounded-md shadow-sm w-5/6" v-model="q" placeholder="Buscar enfermedad...">
                            <Link :href="route('enfermedad.create')"
                                  class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Crear
                            </Link>
                        </div>

                        <hr class="my-6">


                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                    <th>Id</th>
                                    <th>Nombre de Enfermedad</th>
                                    <th>Descripción</th>
                                    <th>Ultima actualización</th>
                                    <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="enfermedad in enfermedades.data" :key="enfermedad.id">
                                        <td class="px-4 py-2"> {{ enfermedad.id }}</td>
                                        <td class="px-4 py-2"> {{ enfermedad.nombre }} </td>
                                        <td class="px-4 py-2"> {{ enfermedad.descripcion }} </td>
                                        <td class="px-4 py-2"> {{ enfermedad.updated_at }} </td>
                                        <td class="px-4 py-2">
                                            <div class="inline-flex rounded-md shadow-sm" role="group">
                                                <Link :href="route('enfermedad.edit', enfermedad.id)"
                                                      class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Actualizar
                                                </Link>
                                                <Link href="" @click.prevent="destroy(enfermedad.id)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Eliminar
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div name="Pagination">
                            <Pagination class="mt-6" :links="enfermedades.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
