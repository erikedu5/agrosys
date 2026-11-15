<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { formatFecha } from '@/utils/formato';
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
            <PageHeader :title="`Transferencia ${transferencia.folio || '#' + transferencia.id}`"
                :subtitle="`${transferencia.sucursal_origen?.nombre ?? '—'} → ${transferencia.sucursal_destino?.nombre ?? '—'}`"
                :back="route('transferencias.index')">
                <a v-if="transferencia.status === 'completado'" :href="route('transferencias.recibo', transferencia.id)" target="_blank" class="ui-btn w-full sm:w-auto">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4" /></svg>
                    Imprimir recibo
                </a>
            </PageHeader>
        </template>

        <div class="ui-page">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="ui-card p-4">
                    <p class="ui-muted">Folio</p>
                    <p class="text-lg font-bold">{{ transferencia.folio || 'N/A' }}</p>
                </div>
                <div class="ui-card p-4">
                    <p class="ui-muted">Estado</p>
                    <span class="ui-badge mt-1 text-sm capitalize" :class="statusColor(transferencia.status)">{{ transferencia.status }}</span>
                </div>
                <div class="ui-card p-4">
                    <p class="ui-muted">Sucursal origen</p>
                    <p class="text-lg font-bold">{{ transferencia.sucursal_origen?.nombre }}</p>
                </div>
                <div class="ui-card p-4">
                    <p class="ui-muted">Sucursal destino</p>
                    <p class="text-lg font-bold">{{ transferencia.sucursal_destino?.nombre }}</p>
                </div>
                <div class="ui-card col-span-2 p-4">
                    <p class="ui-muted">Enviado por</p>
                    <p class="font-bold">{{ transferencia.usuario_envia?.name || 'N/A' }}</p>
                    <p v-if="transferencia.fecha_envio" class="text-[13px] text-gray-600 dark:text-gray-400">{{ formatFecha(transferencia.fecha_envio, true) }}</p>
                </div>
                <div class="ui-card col-span-2 p-4">
                    <p class="ui-muted">Recibido por</p>
                    <p class="font-bold">{{ transferencia.usuario_recibe?.name || 'Pendiente' }}</p>
                    <p v-if="transferencia.fecha_recepcion" class="text-[13px] text-gray-600 dark:text-gray-400">{{ formatFecha(transferencia.fecha_recepcion, true) }}</p>
                </div>
                <div v-if="transferencia.observaciones" class="ui-card col-span-2 p-4 lg:col-span-4">
                    <p class="ui-muted">Observaciones</p>
                    <p class="mt-1">{{ transferencia.observaciones }}</p>
                </div>
            </div>

            <div v-if="canReceive" class="flex flex-col gap-2">
                <button type="button" @click="recibir" :disabled="form.processing" class="ui-btn ui-btn-success ui-btn-lg h-16 w-full text-lg">
                    <svg v-if="!form.processing" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
                    {{ form.processing ? 'Procesando...' : 'Confirmar recepción de mercancía' }}
                </button>
                <p class="ui-help text-center">Al confirmar, se deducirá el stock de la sucursal origen y se agregará al inventario de esta sucursal.</p>
            </div>

            <section class="ui-card overflow-hidden">
                <h2 class="ui-section-title p-4">Productos</h2>
                <div v-for="detalle in transferencia.detalles" :key="detalle.id" class="ui-row">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">{{ nombreProducto(detalle.producto) }}</p>
                        <p v-if="detalle.lote_origen_id" class="text-[13px] text-gray-600 dark:text-gray-400">Lote ID: {{ detalle.lote_origen_id }}</p>
                    </div>
                    <span class="text-lg font-extrabold">{{ detalle.cantidad }}</span>
                </div>
                <p v-if="!transferencia.detalles || transferencia.detalles.length === 0" class="ui-row ui-muted">No hay productos en esta transferencia.</p>
            </section>
        </div>
    </AppLayout>
</template>
