<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch } from 'vue';
    import { router, Link, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue'

    defineProps({
        clasificaciones: {
            type: Array,
            default: []
        }
    })

    const q = ref('');  

    watch(q, (value) => {
        router.get( route( 'clasificacion.index', { q: value } ), {}, { preserveState: true } );
    });

    const destroy = (id) => {
        if (confirm('¿Desea eliminar?')) {
            useForm({}).delete(route('clasificacion.destroy', id));
        }
    }
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Catalogo de clasificaciones
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
                            <input type="text" class="form-input rounded-md shadow-sm w-5/6" v-model="q" placeholder="Buscar clasificacion...">
                            <Link :href="route('clasificacion.create')"  
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
                                    <th>Nombre de Clasificación</th>
                                    <th>Criterio de Clasificación</th>
                                    <th>Ultima actualización</th>
                                    <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="clasificacion in clasificaciones.data">
                                        <td class="px-4 py-2"> {{ clasificacion.id }}</td>
                                        <td class="px-4 py-2"> {{ clasificacion.nombre }} </td>
                                        <td class="px-4 py-2"> {{ clasificacion.criterio }} </td>
                                        <td class="px-4 py-2"> {{ clasificacion.updated_at }} </td>
                                        <td class="px-4 py-2">
                                            <div class="inline-flex rounded-md shadow-sm" role="group">
                                                <Link :href="route('clasificacion.edit', clasificacion.id)" 
                                                      class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Actualizar
                                                </Link>
                                                <Link href="" @click.prevent="destroy(clasificacion.id)" 
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
                            <Pagination class="mt-6" :links="clasificaciones.links" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-none w-14 h-14">
            </div>
        </div>
    </AppLayout>
</template>
