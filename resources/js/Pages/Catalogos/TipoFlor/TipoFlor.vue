<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'

defineProps({
    tipoFlor: {
        type: Object,
        default: {}
    }
})

const q = ref('');

watch(q, (value) => {
    router.get(route('tipoFlor.index', { q: value }), {}, { preserveState: true });
});

const destroy = (id) => {
    if (confirm('¿Desea eliminar?')) {
        router.delete(route('tipoFlor.destroy', { id: id }), {});
    }
}

</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Catalogo de Tipo de flores
            </h2>
            <br>
            <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                placeholder="Buscar Tipo de Flor...">

        </template>

        <hr class="my-6">
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 justify-end">
            <Link :href="route('tipoFlor.create')"
                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                                                          hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                                                          focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                                                          dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
            Agregar tipo de flor +
            </Link>
        </div>
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">

            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="tipo in tipoFlor.data" :key="tipo.id" class="rounded-lg boder p-4 bg-white shadow-lg">
                    <div class="text-sm text-gray-500">Nombre de Tipo de Flor</div>
                    <div class="font-semibold text-gray-900">{{ tipo.nombre }}</div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">Nombre de Enfermedades que Afectan</div>
                            <div>{{ tipo.enfermedades }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Ultima actualización </div>
                            <div>{{ tipo.tamano }}</div>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <div class="inline-flex rounded-md shadow-sm" role="group">
                            <Link :href="route('tipoFlor.edit', tipo.id)" class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg
                                                        hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700
                                                        dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600
                                                        dark:focus:ring-blue-500 dark:focus:text-white">
                            Actualizar
                            </Link>
                            <Link href="" @click.prevent="destroy(tipo.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md
                                                           hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                                           focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white
                                                           dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Eliminar
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Paginación -->
                <div name="Pagination">
                    <Pagination class="mt-6" :links="tipoFlor.links" />
                </div>

            </div>

            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id </th>
                            <th>Nombre de Tipo de Flor </th>
                            <th>Nombre de Enfermedades que Afectan </th>
                            <th>Ultima actualización </th>
                            <th>Acciones </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tipo in tipoFlor.data" :key="tipo.id">
                            <td class="px-4 py-2"> {{ tipo.id }}</td>
                            <td class="px-4 py-2"> {{ tipo.nombre }} </td>
                            <td class="px-4 py-2"> {{ tipo.enfermedades }} </td>
                            <td class="px-4 py-2"> {{ tipo.updated_at }} </td>
                            <td class="px-4 py-2">
                                <div class="inline-flex rounded-md shadow-sm" role="group">
                                    <Link :href="route('tipoFlor.edit', tipo.id)" class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg
                                                        hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700
                                                        dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600
                                                        dark:focus:ring-blue-500 dark:focus:text-white">
                                    Actualizar
                                    </Link>
                                    <Link href="" @click.prevent="destroy(tipo.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md
                                                           hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                                           focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white
                                                           dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Eliminar
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div name="Pagination">
                    <Pagination class="mt-6" :links="tipoFlor.links" />
                </div>
            </div>

        </div>

    </AppLayout>
</template>
