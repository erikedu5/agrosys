<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const ventaId = ref('');

const buscar = () => {
    if (ventaId.value) {
        router.get(route('venta.devolucion.create', ventaId.value));
    }
};
</script>

<template>
    <AppLayout title="Devolución">
        <h1 class="text-xl font-bold mb-4">Buscar venta para devolución</h1>
        <div class="mb-4">
            <label class="block mb-1">ID de la venta</label>
            <input type="number" v-model="ventaId" class="border rounded px-2 py-1" />
        </div>
        <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded" @click="buscar">Buscar</button>
    </AppLayout>
</template>

<style scoped>
</style>
