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
            <br>
            <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                placeholder="Buscar sucursal...">
        </template>

        <hr class="my-6">
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-3 justify-end">
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
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="sucursal in sucursales.data" :key="sucursal.id"
                    class="rounded-lg boder p-4 bg-white shadow-lg">
                    <div>
                        <div class="text-sm text-gray-500">Nombre de la sucursal</div>
                        <div class="font-semibold text-gray-900">{{ sucursal.nombre }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Dirección</div>
                        <div>{{ sucursal.direccion }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Telefono</div>
                        <div>{{ sucursal.telefono }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Email</div>
                        <div>{{ sucursal.email }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Es Matriz</div>
                        <div>{{ sucursal.es_matriz == 1 ? 'Si' : 'No' }}</div>
                    </div>
                    <div class="flex justify-end mt-3">
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
                    </div>

                    <div name="Pagination">
                        <Pagination class="mt-6" :links="sucursales.links" />
                    </div>
                </div>
            </div>

            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id</th>
                            <th>Nombre de la sucursal</th>
                            <th class="text-center">Dirección</th>
                            <th class="text-center">Telefono</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">Es Matriz</th>
                            <th class=" text-center">Accioness</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sucursal in sucursales.data" :key="sucursal.id">
                            <td class="whitespace-nowrap text-center"> {{ sucursal.id }}</td>
                            <td class="font-medium text-gray-900"> {{ sucursal.nombre }} </td>
                            <td class="whitespace-nowrap"> {{ sucursal.direccion }} </td>
                            <td class="whitespace-nowrap"> {{ sucursal.telefono }} </td>
                            <td class="whitespace-nowrap"> {{ sucursal.email }} </td>
                            <td class="whitespace-nowrap text-center"> {{ sucursal.es_matriz == 1 ? 'Si' : 'No' }} </td>
                            <td class="whitespace-nowrap">
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
                <div name="Pagination">
                    <Pagination class="mt-6" :links="sucursales.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
