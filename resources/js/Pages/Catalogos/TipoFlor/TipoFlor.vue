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
    tipoFlor: {
        type: Object,
        default: {}
    }
})

const q = ref('');

watch(q, (value) => {
    router.get(route('tipoFlor.index', { q: value }), {}, { preserveState: true });
});

const destroy = (id) => {
    if (confirm('¿Desea eliminar?')) {
        router.delete(route('tipoFlor.destroy', { id: id }), {});
    }
}

</script>

<template>
    <AppLayout title="Tipos de flor">
        <template #header>
            <PageHeader title="Tipos de flor" subtitle="Catálogo de tipos de flor">
                <Link :href="route('tipoFlor.create')" class="ui-btn ui-btn-primary w-full sm:w-auto">Agregar tipo de flor</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar tipo de flor" />

            <div class="ui-grid">
                <RecordCard v-for="tipo in tipoFlor.data" :key="tipo.id" :eyebrow="`#${tipo.id}`" :title="tipo.nombre"
                    :fields="[
                        { label: 'Enfermedades que la afectan', value: tipo.enfermedades, wide: true },
                        { label: 'Última actualización', value: formatFecha(tipo.updated_at), wide: true },
                    ]">
                    <template #actions>
                        <Link :href="route('tipoFlor.edit', tipo.id)" class="ui-btn flex-1">Editar</Link>
                        <button type="button" class="ui-btn ui-btn-danger flex-1" @click="destroy(tipo.id)">Eliminar</button>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!tipoFlor.data?.length" :title="q ? `No encontramos “${q}”` : 'Aún no hay tipos de flor'" text="Revisa la escritura o agrega un tipo de flor nuevo." />
            <Pagination :links="tipoFlor.links" />
        </div>
    </AppLayout>
</template>
