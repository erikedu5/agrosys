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
    marcas: {
        type: Object,
        default: {}
    }
})

const q = ref('');

watch(q, (value) => {
    router.get(route('marca.index', { q: value }), {}, { preserveState: true });
});

const destroy = (id) => {
    if (confirm('¿Desea eliminar?')) {
        router.delete(route('marca.destroy', { id: id }), {});
    }
}

</script>

<template>
    <AppLayout title="Marcas">
        <template #header>
            <PageHeader title="Marcas" subtitle="Catálogo de marcas">
                <Link :href="route('marca.create')" class="ui-btn ui-btn-primary w-full sm:w-auto">Agregar marca</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar marca" />

            <div class="ui-grid">
                <RecordCard v-for="marca in marcas.data" :key="marca.id" :eyebrow="`#${marca.id}`" :title="marca.nombre"
                    :fields="[{ label: 'Última actualización', value: formatFecha(marca.updated_at), wide: true }]">
                    <template #actions>
                        <Link :href="route('marca.edit', marca.id)" class="ui-btn flex-1">Editar</Link>
                        <button type="button" class="ui-btn ui-btn-danger flex-1" @click="destroy(marca.id)">Eliminar</button>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!marcas.data?.length" :title="q ? `No encontramos “${q}”` : 'Aún no hay marcas'" text="Revisa la escritura o agrega una marca nueva." />
            <Pagination :links="marcas.links" />
        </div>
    </AppLayout>
</template>
