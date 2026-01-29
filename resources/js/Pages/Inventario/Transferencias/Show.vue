<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    transferencia: Object,
    sucursalActiva: Object
});

const page = usePage();

const canReceive = computed(() => {
    const sucursalActivaId = props.sucursalActiva?.id || props.sucursalActiva;
    return props.transferencia.status === 'pendiente' && 
           props.transferencia.id_sucursal_destino === sucursalActivaId;
});

const form = useForm({});

const recibir = () => {
    if (confirm('¿Confirmar recepción de esta transferencia? Esto deducirá el stock del origen.')) {
        form.post(route('transferencias.recibir', props.transferencia.id), {
            preserveScroll: true,
        });
    }
};

const statusColor = (status) => {
    switch (status) {
        case 'completado': return 'bg-green-100 text-green-800';
        case 'pendiente': return 'bg-yellow-100 text-yellow-800';
        case 'cancelado': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const nombreProducto = (producto) => {
    if (producto?.nombre) {
        return producto.deleted_at ? `${producto.nombre} (BORRADO)` : producto.nombre;
    }
    return 'Producto (BORRADO)';
};
</script>

<template>
    <AppLayout title="Detalle Transferencia">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Transferencia {{ transferencia.folio || '#' + transferencia.id }}
                </h2>
                <Link :href="route('transferencias.index')" class="text-indigo-600 hover:text-indigo-900 text-sm">
                    &larr; Volver al listado
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header Info -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Folio</p>
                            <p class="text-lg font-bold text-gray-900">{{ transferencia.folio || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Sucursal Origen</p>
                            <p class="text-lg font-bold text-gray-900">{{ transferencia.sucursal_origen?.nombre }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Sucursal Destino</p>
                            <p class="text-lg font-bold text-gray-900">{{ transferencia.sucursal_destino?.nombre }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Estado</p>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full mt-1" :class="statusColor(transferencia.status)">
                                {{ transferencia.status.charAt(0).toUpperCase() + transferencia.status.slice(1) }}
                            </span>
                        </div>
                        
                        <!-- Usuario que envía -->
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Enviado por</p>
                            <p class="text-gray-900">{{ transferencia.usuario_envia?.name || 'N/A' }}</p>
                            <p class="text-xs text-gray-500" v-if="transferencia.fecha_envio">
                                {{ new Date(transferencia.fecha_envio).toLocaleString() }}
                            </p>
                        </div>
                        
                        <!-- Usuario que recibe -->
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Recibido por</p>
                            <p class="text-gray-900">{{ transferencia.usuario_recibe?.name || 'Pendiente' }}</p>
                            <p class="text-xs text-gray-500" v-if="transferencia.fecha_recepcion">
                                {{ new Date(transferencia.fecha_recepcion).toLocaleString() }}
                            </p>
                        </div>
                        
                        <div class="col-span-1 md:col-span-2 lg:col-span-4" v-if="transferencia.observaciones">
                            <p class="text-sm text-gray-500 font-medium">Observaciones</p>
                            <p class="text-gray-700 bg-gray-50 p-2 rounded mt-1">{{ transferencia.observaciones }}</p>
                        </div>
                    </div>
                </div>

                <!-- Botón Recibir -->
                <div v-if="canReceive" class="mb-6">
                    <button 
                        @click="recibir"
                        :disabled="form.processing"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition-colors disabled:opacity-50"
                    >
                        <span v-if="!form.processing">✓ Confirmar Recepción de Mercancía</span>
                        <span v-else>Procesando...</span>
                    </button>
                    <p class="text-sm text-gray-600 mt-2 text-center">
                        Al confirmar, se deducirá el stock de la sucursal origen y se agregará al inventario de esta sucursal.
                    </p>
                </div>

                <!-- Botón Imprimir Recibo (solo si está completado) -->
                <div v-if="transferencia.status === 'completado'" class="mb-6">
                    <a 
                        :href="route('transferencias.recibo', transferencia.id)"
                        target="_blank"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors inline-flex items-center justify-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Imprimir Recibo de Transferencia
                    </a>
                </div>

                <!-- Action Button Removed: Automatic Transfers -->

                <!-- Products Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900">Detalles de Productos</h3>
                    </div>
                    <!-- Vista móvil en tarjetas -->
                    <div class="md:hidden p-4 grid grid-cols-1 gap-4">
                        <div v-for="detalle in transferencia.detalles" :key="detalle.id" class="rounded-lg border p-4 bg-white shadow-sm">
                            <div class="text-sm text-gray-500">Producto</div>
                            <div class="font-semibold text-gray-900">{{ nombreProducto(detalle.producto) }}</div>
                            <div class="mt-2 text-sm text-gray-500" v-if="detalle.lote_origen_id">Lote ID: {{ detalle.lote_origen_id }}</div>
                            <div class="mt-3">
                                <div class="text-sm text-gray-500">Cantidad Transferida</div>
                                <div class="text-lg font-bold text-gray-900">{{ detalle.cantidad }}</div>
                            </div>
                        </div>
                        <div v-if="!transferencia.detalles || transferencia.detalles.length === 0" class="text-center text-gray-500">
                            No hay productos en esta transferencia.
                        </div>
                    </div>

                    <!-- Tabla desktop -->
                    <div class="hidden md:block p-0 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad Transferida</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="detalle in transferencia.detalles" :key="detalle.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ nombreProducto(detalle.producto) }}</div>
                                        <div class="text-xs text-gray-500" v-if="detalle.lote_origen_id">Lote ID: {{ detalle.lote_origen_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                        {{ detalle.cantidad }}
                                    </td>
                                </tr>
                                <tr v-if="!transferencia.detalles || transferencia.detalles.length === 0">
                                    <td colspan="2" class="px-6 py-4 text-center text-gray-500">No hay productos en esta transferencia.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
