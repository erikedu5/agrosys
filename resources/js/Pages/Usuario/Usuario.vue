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
const props = defineProps({
    usuarios: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    showDeleted: {
        type: Boolean,
        default: false
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    }
});

const q = ref('');
const showDeleted = ref(props.showDeleted);

watch(q, (value) => {
    router.get(route('usuario.index', { q: value, deleted: showDeleted.value }), {}, { preserveState: true });
});

const toggleDeleted = () => {
    showDeleted.value = !showDeleted.value;
    router.get(route('usuario.index', { q: q.value, deleted: showDeleted.value }), {}, { preserveState: true });
};

const desactivar = (id) => {
    if (confirm("¿Desea desactivar el usuario?")) {
        useForm({}).delete(route('usuario.destroy', id));
    }
};

const restaurar = (id) => {
    if (confirm("¿Desea restaurar el usuario?")) {
        useForm({}).put(route('usuario.restore', id));
    }
};
</script>

<template>
    <AppLayout title="Usuarios">
        <template #header>
            <PageHeader title="Usuarios" :subtitle="showDeleted ? 'Usuarios desactivados' : 'Usuarios activos'">
                <button type="button" class="ui-btn flex-1 sm:flex-none" @click="toggleDeleted">{{ showDeleted ? 'Ver activos' : 'Ver eliminados' }}</button>
                <Link :href="route('usuario.create')" class="ui-btn ui-btn-primary flex-1 sm:flex-none">Crear usuario</Link>
            </PageHeader>
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar usuario" />

            <div class="ui-grid">
                <RecordCard v-for="usuario in usuarios.data" :key="usuario.id" :eyebrow="`#${usuario.id}`" :title="usuario.name" :subtitle="usuario.email"
                    :fields="[
                        isSuperAdmin
                            ? { label: 'Empresa', value: usuario.empresa ? usuario.empresa.nombre : 'Sin empresa asignada' }
                            : { label: 'Sucursal', value: usuario.sucursal ? usuario.sucursal.nombre : 'Sin sucursal asignada' },
                        { label: 'Última actualización', value: formatFecha(usuario.updated_at) },
                    ]">
                    <template #badge>
                        <span class="ui-badge ui-badge-blue capitalize">{{ usuario.tipo }}</span>
                    </template>
                    <template #actions>
                        <template v-if="!showDeleted">
                            <Link :href="route('usuario.edit', usuario.id)" class="ui-btn flex-1">Editar</Link>
                            <button type="button" class="ui-btn ui-btn-danger flex-1" @click="desactivar(usuario.id)">Desactivar</button>
                        </template>
                        <button v-else type="button" class="ui-btn flex-1" @click="restaurar(usuario.id)">Restaurar</button>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!usuarios.data?.length" :title="q ? `No encontramos “${q}”` : 'No hay usuarios en esta vista'" />
            <Pagination :links="usuarios.links" />
        </div>
    </AppLayout>
</template>
