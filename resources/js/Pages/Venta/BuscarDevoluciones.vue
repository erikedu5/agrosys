<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Devoluciones</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <label class="block mb-2">Venta de hoy</label>
                    <vue-single-select
                        v-model="venta"
                        :options="ventasHoy"
                        option-key="id"
                        option-label="label"
                        placeholder="Seleccione una venta"
                    />
                    <div class="mt-4">
                        <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded" @click="buscar">Continuar</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
</style>
