<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchField from '@/Components/SearchField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import PaginationBar from '@/Components/PaginationBar.vue'
const props = defineProps({
    sucursales: {
        type: Array,
        default: []
    },
    conteo: Number,
    empresa: Object,
    showDeleted: {
        type: Boolean,
        default: false
    }
});

const q = ref('');
const showDeleted = ref(props.showDeleted);

watch(q, (value) => {
    router.get(route('sucursal.index', { q: value, deleted: showDeleted.value }), {}, { preserveState: true });
});

const toggleDeleted = () => {
    showDeleted.value = !showDeleted.value;
    router.get(route('sucursal.index', { q: q.value, deleted: showDeleted.value }), {}, { preserveState: true });
};

const desactivar = (id) => {
    if (confirm("¿Desea desactivar la sucursal?")) {
        useForm({}).delete(route('sucursal.destroy', id));
    }
};

const restaurar = (id) => {
    if (confirm("¿Desea restaurar la sucursal?")) {
        useForm({}).put(route('sucursal.restore', id));
    }
};
</script>

<template>
    <AppLayout title="Sucursales">
        <template #header>
            <PageHeader title="Sucursales" :subtitle="showDeleted ? 'Sucursales desactivadas' : 'Sucursales activas'">
                <button type="button" class="ui-btn flex-1 sm:flex-none" @click="toggleDeleted">{{ showDeleted ? 'Ver activas' : 'Ver eliminadas' }}</button>
                <Link v-if="conteo < empresa.numero_sucursales" :href="route('sucursal.create')" class="ui-btn ui-btn-primary flex-1 sm:flex-none">Crear sucursal</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar sucursal" />

            <div class="ui-grid">
                <RecordCard v-for="sucursal in sucursales.data" :key="sucursal.id" :eyebrow="`#${sucursal.id}`" :title="sucursal.nombre"
                    :fields="[
                        { label: 'Dirección', value: sucursal.direccion, wide: true },
                        { label: 'Teléfono', value: sucursal.telefono },
                        { label: 'Email', value: sucursal.email },
                    ]">
                    <template #badge>
                        <span v-if="sucursal.es_matriz == 1" class="ui-badge ui-badge-blue">Matriz</span>
                        <span v-if="sucursal.es_bodega == 1" class="ui-badge ui-badge-green">Bodega</span>
                    </template>
                    <template #actions>
                        <template v-if="!showDeleted">
                            <Link :href="route('sucursal.edit', sucursal.id)" class="ui-btn flex-1">Editar</Link>
                            <button type="button" class="ui-btn ui-btn-danger flex-1" @click="desactivar(sucursal.id)">Desactivar</button>
                        </template>
                        <button v-else type="button" class="ui-btn flex-1" @click="restaurar(sucursal.id)">Restaurar</button>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!sucursales.data?.length" :title="q ? `No encontramos “${q}”` : 'No hay sucursales en esta vista'" />
            <PaginationBar :paginator="sucursales" />
        </div>
    </AppLayout>
</template>
