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
                        <button type="button" 
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                        hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                        focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                        dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                        @click="buscar">Continuar</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
</style>
