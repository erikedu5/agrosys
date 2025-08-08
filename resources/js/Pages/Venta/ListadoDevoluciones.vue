<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  devoluciones: { type: Array, default: [] }
});
</script>

<template>
  <AppLayout title="Devoluciones">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-4">Listado de devoluciones</h1>
      <table class="min-w-full bg-white">
        <thead>
          <tr class="text-left">
            <th class="px-2 py-1">ID</th>
            <th class="px-2 py-1">Venta</th>
            <th class="px-2 py-1">Total devuelto</th>
            <th class="px-2 py-1">Fecha</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in devoluciones" :key="d.id" class="border-t">
            <td class="px-2 py-1">{{ d.id }}</td>
            <td class="px-2 py-1">{{ d.venta_id }}</td>
            <td class="px-2 py-1">${{ d.total }}</td>
            <td class="px-2 py-1">{{ d.fecha }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

