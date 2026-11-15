<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchField from '@/Components/SearchField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { formatFecha, formatMoneda } from '@/utils/formato';
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'

defineProps({
    compras: {
        type: Object,
        default: {}
    }
});

const q = ref('');

const statusClass = (status) => {
    switch (status) {
        case 'pagada':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'adeudo':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'retrasada':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100';
    }
};

watch(q, (value) => {
    router.get(route('compra.index', { q: value }), {}, { preserveState: true });
});
</script>

<template>
    <AppLayout title="Compras a proveedores">
        <template #header>
            <PageHeader title="Compras a proveedores" subtitle="Compras, créditos y abonos">
                <Link :href="route('compra.create')" class="ui-btn ui-btn-primary w-full sm:w-auto">Nueva compra</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar compra o proveedor" />

            <div class="ui-grid">
                <RecordCard v-for="compra in compras.data" :key="compra.id" :eyebrow="`Compra #${compra.id}`" :title="compra.proveedor"
                    :fields="[
                        { label: 'Fecha de compra', value: formatFecha(compra.fecha_compra) },
                        { label: 'Límite de crédito', value: formatFecha(compra.fecha_credito) },
                        { label: 'Saldo a crédito', value: formatMoneda(compra.total_credito), wide: true },
                    ]">
                    <template #badge>
                        <span class="ui-badge capitalize" :class="statusClass(compra.status)">{{ compra.status }}</span>
                    </template>
                    <p class="text-xl font-extrabold">{{ formatMoneda(compra.total_compra) }}</p>
                    <template #actions>
                        <Link :href="route('compra.show', compra.id)" class="ui-btn flex-1">Ver detalle</Link>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!compras.data?.length" :title="q ? `No encontramos “${q}”` : 'Aún no hay compras'" text="Registra una compra a proveedor para verla aquí." />
            <Pagination :links="compras.links" />
        </div>
    </AppLayout>
</template>
