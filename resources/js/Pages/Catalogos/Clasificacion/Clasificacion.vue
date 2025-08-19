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
    router.get(route('clasificacion.index', { q: value }), {}, { preserveState: true });
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
            <br>
            <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                placeholder="Buscar clasificacion...">
        </template>

        <hr class="my-6">
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 justify-end">
            <Link :href="route('clasificacion.create')"
                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
            Agregar clasificación +
            </Link>
        </div>
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3 mb-3">
            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="clasificacion in clasificaciones.data" :key="clasificacion.id"
                    class="rounded-lg boder p-4 bg-white shadow-lg">
                    <div class="text-sm text-gray-500">Nombre de Clasificación</div>
                    <div class="font-semibold text-gray-900">{{ clasificacion.nombre }}</div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">Criterio de Clasificación</div>
                            <div>{{ clasificacion.criterio }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Ultima actualización</div>
                            <div>{{ clasificacion.updated_at }}</div>
                        </div>
                    </div>
                    <div class="flex justify-end mt-3">
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
                    </div>
                </div>
                <div name="Pagination">
                    <Pagination class="mt-6" :links="clasificaciones.links" />
                </div>
            </div>
            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id</th>
                            <th>Nombre de Clasificación</th>
                            <th>Criterio de Clasificación</th>
                            <th>Ultima actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="clasificacion in clasificaciones.data" :key="clasificacion.id">
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
                <div name="Pagination">
                    <Pagination class="mt-6" :links="clasificaciones.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
