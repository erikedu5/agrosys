<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'

defineProps({
    clientes: {
        type: Object,
        default: {}
    }
});

const q = ref('');

watch(q, (value) => {
    router.get(route('cliente.index', { q: value }), {}, { preserveState: true });
});

const desactivar = (id) => {
    if (confirm("¿Desea desactivar el cliente?")) {
        useForm({}).delete(route('cliente.destroy', id));
    }
}
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Clientes
            </h2>
            <br>
            <div class="flex justify-between">
                <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                    placeholder="Buscar cliente...">
            </div>
        </template>

        <hr class="my-6">
        <div class="flex justify-end max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-3">
            <Link :href="route('cliente.create')"
                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
            Crear cliente +
            </Link>
        </div>
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="cliente in clientes.data" :key="cliente.id" class="rounded-lg boder p-4 bg-white shadow-lg">
                    <div class="text-sm text-gray-500">Nombre del cliente</div>
                    <div class="font-semibold text-gray-900">{{ cliente.nombre }}</div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">Porcentaje de descuento</div>
                            <div>{{ cliente.porcentaje_descuento }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Requiere factura</div>
                            <div>{{ cliente.requiereFactura == 0 ? 'No' : 'Si' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">RFC</div>
                            <div>{{ cliente.rfc }}</div>
                        </div>
                    </div>
                    <div class="flex justify-end mt-3">
                        <div class="inline-flex rounded-md shadow-sm" role="group">
                            <Link :href="route('cliente.edit', cliente.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Actualizar
                            </Link>
                            <Link href="" @click.prevent="desactivar(cliente.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200  hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Desactivar cliente
                            </Link>
                            <Link :href="route('venta.show', cliente.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Ver credito
                            </Link>
                        </div>
                    </div>
                    <div name="Pagination">
                        <Pagination class="mt-6" :links="clientes.links" />
                    </div>
                </div>
            </div>

            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id</th>
                            <th>Nombre del cliente</th>
                            <th>Porcentaje de descuento</th>
                            <th>Requiere factura</th>
                            <th>RFC</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-2">
                        <tr v-for="cliente in clientes.data" :key="cliente.id" class="border-b">
                            <td class="whitespace-nowrap"> {{ cliente.id }}</td>
                            <td class="font-medium text-gray-900"> {{ cliente.nombre }} </td>
                            <td class="whitespace-nowrap"> {{ cliente.porcentaje_descuento }} % </td>
                            <td class="whitespace-nowrap"> {{ cliente.requiereFactura == 0 ? 'No' : 'Si' }}</td>
                            <td class="whitespace-nowrap"> {{ cliente.rfc }}</td>
                            <td class="whitespace-nowrap">
                                <div class="inline-flex rounded-md shadow-sm" role="group">
                                    <Link :href="route('cliente.edit', cliente.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Actualizar
                                    </Link>
                                    <Link href="" @click.prevent="desactivar(cliente.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200  hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Desactivar cliente
                                    </Link>
                                    <Link :href="route('venta.show', cliente.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Ver credito
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div name="Pagination">
                    <Pagination class="mt-6" :links="clientes.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
