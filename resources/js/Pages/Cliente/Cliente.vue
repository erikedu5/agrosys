<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchField from '@/Components/SearchField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { formatMoneda } from '@/utils/formato';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'
import { etiquetaTipoPrecio } from '@/utils/precios';

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
    <AppLayout title="Clientes">
        <template #header>
            <PageHeader title="Clientes" subtitle="Precios, crédito y datos de facturación">
                <Link :href="route('cliente.create')" class="ui-btn ui-btn-primary w-full sm:w-auto">Crear cliente</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar cliente" />

            <div class="ui-grid">
                <RecordCard v-for="cliente in clientes.data" :key="cliente.id" :eyebrow="`#${cliente.id}`" :title="cliente.nombre"
                    :fields="[
                        { label: 'Precio asignado', value: etiquetaTipoPrecio(cliente.tipo_precio) },
                        { label: 'Límite de crédito', value: cliente.limite_credito != null ? formatMoneda(cliente.limite_credito) : 'Sin límite' },
                        { label: 'Plazo de crédito', value: cliente.plazo_credito_dias != null ? `${cliente.plazo_credito_dias} días` : 'Sin plazo' },
                        { label: 'RFC', value: cliente.rfc },
                    ]">
                    <template #badge>
                        <span v-if="cliente.requiereFactura != 0" class="ui-badge ui-badge-blue">Factura</span>
                    </template>
                    <template #actions>
                        <Link :href="route('cliente.edit', cliente.id)" class="ui-btn flex-1">Editar</Link>
                        <Link :href="route('venta.show', cliente.id)" class="ui-btn flex-1">Ver crédito</Link>
                        <button type="button" class="ui-btn ui-btn-danger flex-1" @click="desactivar(cliente.id)">Desactivar</button>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!clientes.data?.length" :title="q ? `No encontramos “${q}”` : 'Aún no hay clientes'" text="Revisa la escritura o crea un cliente nuevo." />
            <Pagination :links="clientes.links" />
        </div>
    </AppLayout>
</template>
