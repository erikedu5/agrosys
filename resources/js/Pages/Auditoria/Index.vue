<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PaginationBar from '@/Components/PaginationBar.vue';
import { formatFecha } from '@/utils/formato';
import { statusBadge } from '@/utils/auditoria';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    auditorias: { type: Object, default: () => ({ data: [], links: [] }) },
    status: { type: String, default: null },
    etiquetas: { type: Object, default: () => ({}) },
    puedeCrear: { type: Boolean, default: false },
});

const filtrar = (status) => {
    router.get(route('auditorias.index'), status ? { status } : {}, { preserveState: true, replace: true });
};

const progreso = (a) => (a.items_count ? Math.round((a.contados_count * 100) / a.items_count) : 0);
</script>

<template>
    <AppLayout title="Auditorías">
        <template #header>
            <PageHeader title="Auditorías de inventario" subtitle="Conteo físico vs sistema por sucursal o bodega">
                <Link :href="route('auditorias.historico')" class="ui-btn flex-1 sm:flex-none">Histórico</Link>
                <Link v-if="puedeCrear" :href="route('auditorias.create')" class="ui-btn ui-btn-primary flex-1 sm:flex-none">Nueva auditoría</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar por estado">
                <button type="button" class="ui-btn" :class="!status ? 'ui-btn-primary' : ''" @click="filtrar(null)">Todas</button>
                <button v-for="(label, key) in etiquetas" :key="key" type="button" class="ui-btn" :class="status === key ? 'ui-btn-primary' : ''" @click="filtrar(key)">
                    {{ label }}
                </button>
            </div>

            <div class="ui-grid">
                <RecordCard v-for="a in auditorias.data" :key="a.id" :eyebrow="a.folio"
                    :title="`${a.sucursal?.es_bodega ? 'Bodega' : 'Sucursal'} ${a.sucursal?.nombre ?? '—'}`"
                    :subtitle="a.empresa?.nombre"
                    :fields="[
                        { label: 'Alcance', value: a.alcance_tipo === 'total' ? 'Total' : a.alcance_tipo },
                        { label: 'Contados', value: `${a.contados_count} / ${a.items_count}` },
                        { label: 'Inicio', value: formatFecha(a.iniciada_at, true) || 'Sin iniciar' },
                        { label: 'Auditores', value: (a.auditores || []).map(u => u.name).join(', ') },
                    ]">
                    <template #badge>
                        <span :class="statusBadge(a.status)">{{ etiquetas[a.status] ?? a.status }}</span>
                    </template>
                    <div v-if="a.items_count" class="h-2 w-full overflow-hidden rounded bg-gray-100 dark:bg-gray-700" :aria-label="`Progreso ${progreso(a)}%`">
                        <div class="h-2 bg-green-600" :style="{ width: progreso(a) + '%' }"></div>
                    </div>
                    <template #actions>
                        <Link :href="route('auditorias.show', a.id)" class="ui-btn flex-1">Ver</Link>
                        <Link v-if="['en_conteo', 'en_revision'].includes(a.status) && puedeCrear" :href="route('auditorias.captura', a.id)" class="ui-btn ui-btn-primary flex-1">Capturar</Link>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!auditorias.data?.length" title="No hay auditorías"
                :text="puedeCrear ? 'Crea una auditoría eligiendo la sucursal o bodega a contar.' : 'Aún no se han registrado auditorías para tu empresa.'" />
            <PaginationBar :paginator="auditorias" />
        </div>
    </AppLayout>
</template>
