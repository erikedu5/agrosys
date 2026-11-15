<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    ventasHoy: { type: Array, default: () => [] },
});

const venta = ref(null);

const buscar = () => {
    if (venta.value) {
        router.get(route('venta.devoluciones.create', venta.value.id));
    }
};
</script>

<template>
    <AppLayout title="Devolución">
        <template #header>
            <PageHeader title="Nueva devolución" subtitle="Elige la venta de hoy a devolver" :back="route('devoluciones.list')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <div class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                <div class="ui-field">
                    <label class="ui-label">Venta de hoy</label>
                    <vue-single-select
                        v-model="venta"
                        :options="ventasHoy"
                        option-key="id"
                        option-label="label"
                        placeholder="Seleccione una venta"
                    />
                </div>
                <button type="button" class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="!venta" @click="buscar">Continuar</button>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
</style>
