<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchField from '@/Components/SearchField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { formatFecha } from '@/utils/formato';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'

defineProps({
    enfermedades: {
        type: Object,
        default: {}
    }
})

const q = ref('');

watch(q, (value) => {
    router.get(route('enfermedad.index', { q: value }), {}, { preserveState: true });
});

const destroy = (id) => {
    if (confirm('¿Desea eliminar?')) {
        useForm({}).delete(route('enfermedad.destroy', id));
    }
}
</script>

<template>
    <AppLayout title="Enfermedades">
        <template #header>
            <PageHeader title="Enfermedades" subtitle="Catálogo de enfermedades">
                <Link :href="route('enfermedad.create')" class="ui-btn ui-btn-primary w-full sm:w-auto">Agregar enfermedad</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar enfermedad" />

            <div class="ui-grid">
                <RecordCard v-for="enfermedad in enfermedades.data" :key="enfermedad.id" :eyebrow="`#${enfermedad.id}`" :title="enfermedad.nombre"
                    :fields="[
                        { label: 'Descripción', value: enfermedad.descripcion, wide: true },
                        { label: 'Última actualización', value: formatFecha(enfermedad.updated_at), wide: true },
                    ]">
                    <template #actions>
                        <Link :href="route('enfermedad.edit', enfermedad.id)" class="ui-btn flex-1">Editar</Link>
                        <button type="button" class="ui-btn ui-btn-danger flex-1" @click="destroy(enfermedad.id)">Eliminar</button>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!enfermedades.data?.length" :title="q ? `No encontramos “${q}”` : 'Aún no hay enfermedades'" text="Revisa la escritura o agrega una enfermedad nueva." />
            <Pagination :links="enfermedades.links" />
        </div>
    </AppLayout>
</template>
