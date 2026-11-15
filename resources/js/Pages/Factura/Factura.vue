<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { formatFecha, formatMoneda } from '@/utils/formato';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import PaginationBar from '@/Components/PaginationBar.vue'
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { etiquetaFormaPago } from '@/utils/formasPago';

const props = defineProps({
    facturas: {
        type: Object,
        default: {}
    },
    // Rango aplicado por el servidor, en formato yyyy-MM-dd.
    filtros: {
        type: Object,
        default: () => ({ fechaInicio: null, fechaFin: null })
    }
});

// El selector trabaja en yyyy-MM-dd, igual que el servidor, y arranca con el rango que se está mostrando.
const fechas = ref(props.filtros.fechaInicio && props.filtros.fechaFin ? [props.filtros.fechaInicio, props.filtros.fechaFin] : []);
watch(() => props.filtros, (filtros) => {
    if (filtros.fechaInicio && filtros.fechaFin) fechas.value = [filtros.fechaInicio, filtros.fechaFin];
});

const rangoActual = () => {
    const [inicio, fin] = fechas.value ?? [];
    return {
        fechaInicio: inicio || props.filtros.fechaInicio,
        fechaFin: fin || inicio || props.filtros.fechaFin,
    };
};

const filterFacturas = () => {
    router.get(route('facturas.index'), rangoActual(), { preserveState: true, preserveScroll: true });
};

const descargarExcel = () => {
    window.location.href = route('facturas.export', rangoActual());
};

const update = (id) => {
    router.put(route('facturas.update', { id, ...rangoActual() }), {}, { preserveScroll: true });
};

const estatus = (factura) => {
    if (factura.facturaCompleta) return 'Facturada';
    if (factura.factura_status === 'error') return 'Error al facturar';
    return 'No facturada';
};

</script>

<template>
    <AppLayout title="Facturas">
        <template #header>
            <PageHeader title="Facturas" subtitle="Ventas de clientes que requieren factura" />
        </template>

        <div class="ui-page">
            <div class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
                <div class="ui-field flex-1">
                    <span class="ui-label">Rango de fechas</span>
                    <VueDatePicker v-model="fechas" range model-type="yyyy-MM-dd" format="dd/MM/yyyy" :clearable="false"></VueDatePicker>
                </div>
                <button type="button" class="ui-btn ui-btn-primary" @click="filterFacturas">Filtrar</button>
                <button type="button" class="ui-btn ui-btn-success" @click="descargarExcel">Descargar Excel</button>
            </div>

            <div class="ui-grid">
                <RecordCard v-for="factura in facturas.data" :key="factura.id" :eyebrow="`Venta #${factura.venta.id}`"
                    :title="factura.cliente.nombre" :subtitle="factura.cliente.rfc ? `RFC ${factura.cliente.rfc}` : ''"
                    :fields="[
                        { label: 'Fecha de venta', value: formatFecha(factura.venta.created_at, true) },
                        { label: 'Forma de pago', value: etiquetaFormaPago(factura.venta.forma_pago) || 'Sin definir' },
                    ]">
                    <template #badge>
                        <span class="ui-badge"
                            :class="factura.facturaCompleta ? 'ui-badge-green' : factura.factura_status === 'error' ? 'ui-badge-red' : 'ui-badge-amber'">
                            {{ estatus(factura) }}
                        </span>
                    </template>

                    <p class="flex items-end justify-between gap-2">
                        <span class="text-xl font-extrabold">{{ formatMoneda(factura.venta.total) }}</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400">A facturar</span>
                    </p>
                    <p v-if="factura.factura_error && !factura.facturaCompleta" class="rounded-lg bg-red-50 px-3 py-2 text-[13px] font-semibold text-red-800">{{ factura.factura_error }}</p>

                    <template v-if="!factura.facturaCompleta" #actions>
                        <button type="button" class="ui-btn flex-1" @click="update(factura.id)">Marcar como facturada</button>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!facturas.data?.length" title="No hay ventas por facturar" text="No hay ventas por facturar en el rango seleccionado." />
            <PaginationBar :paginator="facturas" />
        </div>
    </AppLayout>
</template>
