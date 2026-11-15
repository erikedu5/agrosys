<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { formatFecha } from '@/utils/formato';
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
            <PageHeader title="Transferencias" subtitle="Movimientos de inventario entre sucursales">
                <Link :href="route('transferencias.create')" class="ui-btn ui-btn-primary w-full sm:w-auto">Nueva transferencia</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <div class="ui-grid">
                <RecordCard v-for="transferencia in transferencias.data" :key="transferencia.id"
                    :eyebrow="transferencia.folio || '#' + transferencia.id"
                    :title="`${transferencia.sucursal_origen?.nombre ?? '—'} → ${transferencia.sucursal_destino?.nombre ?? '—'}`"
                    :fields="[
                        { label: 'Enviado por', value: transferencia.usuario_envia?.name || 'N/A' },
                        { label: 'Fecha de envío', value: formatFecha(transferencia.fecha_envio) },
                        { label: 'Recibido por', value: transferencia.usuario_recibe?.name || 'Pendiente' },
                        { label: 'Fecha de recepción', value: formatFecha(transferencia.fecha_recepcion) },
                        { label: 'Creada', value: formatFecha(transferencia.created_at, true), wide: true },
                    ]">
                    <template #badge>
                        <span class="ui-badge capitalize" :class="statusClass(transferencia.status)">{{ transferencia.status }}</span>
                    </template>
                    <template #actions>
                        <Link :href="route('transferencias.show', transferencia.id)" class="ui-btn flex-1">Ver detalles</Link>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="transferencias.data.length === 0" title="No hay transferencias registradas" text="Crea una transferencia para mover inventario a otra sucursal." />
            <Pagination :links="transferencias.links" />
        </div>
    </AppLayout>
</template>
