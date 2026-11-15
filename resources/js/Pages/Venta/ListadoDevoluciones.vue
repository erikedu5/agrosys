<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { formatFecha, formatMoneda } from '@/utils/formato';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    devoluciones: { type: Array, default: [] }
});

const nombreProducto = (producto) => {
    if (producto?.nombre) {
        return producto.deleted_at ? `${producto.nombre} (BORRADO)` : producto.nombre;
    }
    return 'Producto (BORRADO)';
};
</script>
<template>
    <AppLayout title="Devoluciones">
        <template #header>
            <PageHeader title="Devoluciones" subtitle="Devoluciones registradas">
                <Link :href="route('venta.devoluciones.index')" class="ui-btn ui-btn-primary w-full sm:w-auto">Crear devolución</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <div class="ui-grid">
                <RecordCard v-for="d in devoluciones" :key="d.id" :eyebrow="`Devolución #${d.id}`" :title="`Venta #${d.venta_id}`" :subtitle="formatFecha(d.fecha, true)">
                    <template #badge>
                        <span class="text-lg font-extrabold">{{ formatMoneda(d.total) }}</span>
                    </template>
                    <ul class="overflow-hidden rounded-lg border border-gray-200 text-sm dark:border-gray-700">
                        <li v-for="detalle in d.detalles" :key="detalle.id" class="flex items-center justify-between gap-3 border-t border-gray-100 px-3 py-2 first:border-t-0 dark:border-gray-700">
                            <span class="min-w-0 font-semibold">{{ nombreProducto(detalle.producto) }}</span>
                            <span class="ui-badge shrink-0">{{ detalle.cantidad }} uds.</span>
                        </li>
                    </ul>
                </RecordCard>
            </div>

            <EmptyState v-if="!devoluciones.length" title="No hay devoluciones registradas" />
        </div>
    </AppLayout>
</template>
