<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchField from '@/Components/SearchField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    empresas: {
        type: Array,
        default: []
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    },
    showDeleted: {
        type: Boolean,
        default: false
    },
    all: {
        type: Boolean,
        default: false
    }
});

const q = ref('');
const showDeleted = ref(props.showDeleted);

watch(q, (value) => {
    router.get(route('empresa.index', { q: value, deleted: showDeleted.value }), {}, { preserveState: true });
});

const toggleDeleted = () => {
    showDeleted.value = !showDeleted.value;
    router.get(route('empresa.index', { q: q.value, deleted: showDeleted.value }), {}, { preserveState: true });
};

const desactivar = (id) => {
    if (confirm("¿Desea desactivar la empresa?")) {
        useForm({}).delete(route('empresa.destroy', id));
    }
};

const restaurar = (id) => {
    if (confirm("¿Desea restaurar la empresa?")) {
        useForm({}).put(route('empresa.restore', id));
    }
};
</script>

<template>
    <AppLayout title="Empresas">
        <template #header>
            <PageHeader title="Empresas" :subtitle="showDeleted ? 'Empresas desactivadas' : 'Datos fiscales y configuración'">
                <template v-if="!all">
                    <button type="button" class="ui-btn flex-1 sm:flex-none" @click="toggleDeleted">{{ showDeleted ? 'Ver activos' : 'Ver eliminados' }}</button>
                    <Link v-if="isSuperAdmin" :href="route('empresa.subscriptions.index')" class="ui-btn flex-1 sm:flex-none">Suscripciones</Link>
                    <Link :href="route('empresa.create')" class="ui-btn ui-btn-primary flex-1 sm:flex-none">Crear empresa</Link>
                </template>
            </PageHeader>
        </template>

        <div class="ui-page">
            <SearchField v-model="q" placeholder="Buscar empresa" />

            <div class="ui-grid">
                <RecordCard v-for="empresa in empresas.data" :key="empresa.id" :eyebrow="`#${empresa.id}`" :title="empresa.nombre"
                    :fields="[
                        { label: 'Dirección', value: empresa.direccion, wide: true },
                        { label: 'Teléfono', value: empresa.telefono },
                        { label: 'Email', value: empresa.email },
                        { label: 'RFC', value: empresa.rfc },
                        { label: 'Precio de compra visible', value: empresa.mostrar_campos_precio ? 'Sí' : 'No' },
                        ...(isSuperAdmin || all ? [{ label: 'Facturación automática', value: empresa.enviar_facturas_automaticas ? 'Sí' : 'No' }] : []),
                        ...(isSuperAdmin && empresa.ventas_bloqueadas ? [{ label: 'Motivo de bloqueo', value: empresa.motivo_bloqueo || 'Sin motivo registrado', wide: true }] : []),
                        { label: 'Aviso', value: empresa.aviso, wide: true },
                    ]">
                    <template v-if="isSuperAdmin && empresa.ventas_bloqueadas" #badge>
                        <span class="ui-badge ui-badge-red">Ventas bloqueadas</span>
                    </template>
                    <template #actions>
                        <template v-if="!showDeleted">
                            <Link :href="route('empresa.edit', empresa.id)" class="ui-btn flex-1">Editar</Link>
                            <button v-if="!all" type="button" class="ui-btn ui-btn-danger flex-1" @click="desactivar(empresa.id)">Desactivar</button>
                        </template>
                        <button v-else-if="!all" type="button" class="ui-btn flex-1" @click="restaurar(empresa.id)">Restaurar</button>
                    </template>
                </RecordCard>
            </div>

            <EmptyState v-if="!empresas.data?.length" :title="q ? `No encontramos “${q}”` : 'No hay empresas en esta vista'" />
            <Pagination :links="empresas.links" />
        </div>
    </AppLayout>
</template>
