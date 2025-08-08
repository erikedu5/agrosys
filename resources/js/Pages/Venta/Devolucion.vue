<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    venta: { type: Object, required: true },
    items: { type: Array, default: () => [] },
});

const form = useForm({
    observaciones: '',
    items: props.items.map(i => ({ producto_id: i.producto_id, cantidad: 0 })),
});

const submit = () => {
    form.post(route('venta.devolucion.store', props.venta.id));
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
                    <h1 class="text-xl font-bold mb-4">Devolución de Venta #{{ venta.id }}</h1>
                    <div class="overflow-x-auto">
                        <table class="min-w-full mb-4">
                            <thead>
                                <tr>
                                    <th class="px-2 py-1 text-left">Producto</th>
                                    <th class="px-2 py-1 text-right">Vendido</th>
                                    <th class="px-2 py-1 text-right">Devuelto</th>
                                    <th class="px-2 py-1 text-right">Máx. devolver</th>
                                    <th class="px-2 py-1 text-right">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in items" :key="item.producto_id">
                                    <td class="px-2 py-1">{{ item.producto_nombre }}</td>
                                    <td class="px-2 py-1 text-right">{{ item.vendido }}</td>
                                    <td class="px-2 py-1 text-right">{{ item.devuelto }}</td>
                                    <td class="px-2 py-1 text-right">{{ item.max_devolver }}</td>
                                    <td class="px-2 py-1 text-right">
                                        <input type="number" class="border rounded px-2 py-1 w-24" min="0" :max="item.max_devolver" v-model.number="form.items[index].cantidad" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-4">
                        <label class="block mb-1">Observaciones</label>
                        <textarea class="w-full border rounded px-2 py-1" rows="3" v-model="form.observaciones"></textarea>
                    </div>
                    <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded" @click="submit">Guardar devolución</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
</style>
