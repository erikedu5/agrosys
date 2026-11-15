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
    clasificaciones: {
        type: Object,
        default: {}
    }
})

const q = ref('');

watch(q, (value) => {
    router.get(route('clasificacion.index', { q: value }), {}, { preserveState: true });
});

const destroy = (id) => {
    if (confirm('¿Desea eliminar?')) {
        useForm({}).delete(route('clasificacion.destroy', id));
    }
}
</script>

<template>
    <AppLayout title="Clasificaciones">
        <template #header>
            <PageHeader title="Clasificaciones" subtitle="Catálogo de clasificaciones">
                <Link :href="route('clasificacion.create')" class="ui-btn ui-btn-primary w-full sm:w-auto">Agregar clasificación</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar clasificación" />

            <div class="ui-grid">
                <RecordCard v-for="clasificacion in clasificaciones.data" :key="clasificacion.id" :eyebrow="`#${clasificacion.id}`" :title="clasificacion.nombre"
                    :fields="[
                        { label: 'Criterio', value: clasificacion.criterio },
                        { label: 'Última actualización', value: formatFecha(clasificacion.updated_at) },
                    ]">
                    <template #actions>
                        <Link :href="route('clasificacion.edit', clasificacion.id)" class="ui-btn flex-1">Editar</Link>
                        <button type="button" class="ui-btn ui-btn-danger flex-1" @click="destroy(clasificacion.id)">Eliminar</button>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!clasificaciones.data?.length" :title="q ? `No encontramos “${q}”` : 'Aún no hay clasificaciones'" text="Revisa la escritura o agrega una clasificación nueva." />
            <Pagination :links="clasificaciones.links" />
        </div>
    </AppLayout>
</template>
