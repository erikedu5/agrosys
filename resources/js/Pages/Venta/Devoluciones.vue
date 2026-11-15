<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
    form.post(route('venta.devoluciones.store', props.venta.id));
};
</script>

<template>
    <AppLayout title="Devolución">
        <template #header>
            <PageHeader :title="`Devolución de venta #${venta.id}`" subtitle="Indica la cantidad a devolver por producto" :back="route('venta.devoluciones.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <section class="ui-card overflow-hidden">
                <h2 class="ui-section-title p-4">Productos de la venta</h2>
                <div v-for="(item, index) in items" :key="item.producto_id" class="ui-row flex-wrap">
                    <div class="min-w-0 flex-1 basis-48">
                        <p class="font-semibold">{{ item.producto_nombre }}</p>
                        <p class="text-[13px] text-gray-600 dark:text-gray-400">Vendido {{ item.vendido }} · Devuelto {{ item.devuelto }}</p>
                    </div>
                    <span class="ui-badge" :class="Number(item.max_devolver) > 0 ? 'ui-badge-blue' : ''">Máx. {{ item.max_devolver }}</span>
                    <label class="sr-only" :for="`devolver-${item.producto_id}`">Cantidad a devolver de {{ item.producto_nombre }}</label>
                    <input :id="`devolver-${item.producto_id}`" type="number" step="0.01" min="0" :max="item.max_devolver" v-model.number="form.items[index].cantidad"
                        class="h-11 w-24 rounded-lg border-gray-300 text-center text-lg font-bold text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                </div>
                <p v-if="!items.length" class="ui-row ui-muted">Esta venta no tiene productos por devolver.</p>
            </section>

            <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                <div class="ui-field">
                    <label class="ui-label">Observaciones</label>
                    <textarea class="ui-input" rows="3" v-model="form.observaciones"></textarea>
                </div>
                <button type="button" class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing" @click="submit">Guardar devolución</button>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
</style>
