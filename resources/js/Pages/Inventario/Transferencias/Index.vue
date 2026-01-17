<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    transferencias: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    auth: {
        type: Object,
        default: {}
    }
});

const statusClass = (status) => {
    switch (status) {
        case 'completado': return 'bg-green-100 text-green-800';
        case 'pendiente': return 'bg-yellow-100 text-yellow-800';
        case 'cancelado': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <AppLayout title="Transferencias">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Transferencias de Inventario
                </h2>
                <Link :href="route('transferencias.create')" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                    Nueva Transferencia
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Folio
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Origen → Destino
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Enviado por
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Recibido por
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha Creación
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="transferencia in transferencias.data" :key="transferencia.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ transferencia.folio || '#' + transferencia.id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ transferencia.sucursal_origen?.nombre }}</div>
                                    <div class="text-xs text-gray-500">→ {{ transferencia.sucursal_destino?.nombre }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ transferencia.usuario_envia?.name || 'N/A' }}</div>
                                    <div class="text-xs text-gray-500" v-if="transferencia.fecha_envio">
                                        {{ new Date(transferencia.fecha_envio).toLocaleDateString() }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ transferencia.usuario_recibe?.name || '-' }}</div>
                                    <div class="text-xs text-gray-500" v-if="transferencia.fecha_recepcion">
                                        {{ new Date(transferencia.fecha_recepcion).toLocaleDateString() }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="statusClass(transferencia.status)" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ transferencia.status.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(transferencia.created_at).toLocaleString() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('transferencias.show', transferencia.id)" class="text-indigo-600 hover:text-indigo-900">Ver Detalles</Link>
                                        </td>
                                    </tr>
                                    <tr v-if="transferencias.data.length === 0">
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay transferencias registradas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                         <div class="mt-4">
                            <Pagination :links="transferencias.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
