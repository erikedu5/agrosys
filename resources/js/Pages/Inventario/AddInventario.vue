
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({
        producto: Object,
    });

    const { form, reset } = usePersistedForm('addInventarioForm', {
        nombre: props.producto !== undefined ? props.producto.nombre : '',
        cantidad: 0,
        id: props.producto !== undefined ? props.producto.id: null,
    });

    const submit = () => {
        form.post(route('inventario.addInventario'), {
            onSuccess: reset,
        });
    }
</script>

<template>
    <AppLayout title="Agregar inventario">
        <template #header>
            <PageHeader title="Agregar al inventario" :subtitle="form.nombre" :back="route('inventario.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="ui-card flex flex-col gap-4 p-4 sm:p-5" @submit.prevent="submit">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-900">
                    <p class="ui-muted">Actualmente en existencia · {{ form.nombre }}</p>
                    <p class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ props.producto.cantidad }}</p>
                </div>

                <div class="ui-field">
                    <label class="ui-label">Cantidad a agregar</label>
                    <input type="number" min="1" step="0.01" class="ui-input text-right text-2xl font-bold" v-model="form.cantidad">
                    <InputError :message="form.errors.cantidad" />
                </div>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>
