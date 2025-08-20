<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'

defineProps({
    enfermedades: {
        type: Object,
        default: {}
    }
})

const q = ref('');

watch(q, (value) => {
    router.get(route('enfermedad.index', { q: value }), {}, { preserveState: true });
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
            <br>
            <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                placeholder="Buscar enfermedad...">
        </template>

        <hr class="my-6">
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 justify-end mb-3">
            <Link :href="route('enfermedad.create')"
                class="flex align-center flex-row px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
            <span>Agregar enfermedad +</span>

            </Link>
        </div>
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="enfermedad in enfermedades.data" :key="enfermedad.id"
                    class="rounded-lg boder p-4 bg-white shadow-lg">
                    <div class="text-sm text-gray-500">Nombre de Enfermedad</div>
                    <div class="font-semibold text-gray-900">{{ enfermedad.nombre }}</div>
                    <div class="mt-3 grid grid-cols-1 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">Descripción</div>
                            <div>{{ enfermedad.descripcion }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Ultima actualización</div>
                            <div>{{ enfermedad.updated_at }}</div>
                        </div>
                    </div>
                    <div class="flex justify-end mt-3">
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
                    </div>
                </div>
                <div name="Pagination">
                    <Pagination class="mt-6" :links="enfermedades.links" />
                </div>
            </div>
            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id</th>
                            <th>Nombre de Enfermedad</th>
                            <th>Descripción</th>
                            <th>Ultima actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-2">
                        <tr v-for="enfermedad in enfermedades.data" :key="enfermedad.id" class="border-b">
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
                <div name="Pagination">
                    <Pagination class="mt-6" :links="enfermedades.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
