
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({marca: Object});

    const { form, reset } = usePersistedForm('marcaForm', {
        nombre: props.marca !== undefined ? props.marca.nombre : '',
        id: props.marca !== undefined ? props.marca.id: null
    });

    const submit = () => {
        if (props.marca == undefined) {
            form.post(route('marca.store'), {
                onSuccess: reset,
            });
        } else {
            form.put(route('marca.update', props.marca.id), {
                onSuccess: reset,
            });
        }
    }
</script>

<template>
    <AppLayout title="Marca">
        <template #header>
            <PageHeader :title="marca ? 'Editar marca' : 'Nueva marca'" subtitle="Catálogo de marcas" :back="route('marca.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="ui-card flex flex-col gap-4 p-4 sm:p-5" @submit.prevent="submit">
                <div class="ui-field">
                    <label class="ui-label">Nombre</label>
                    <input type="text" class="ui-input" v-model="form.nombre">
                    <InputError :message="form.errors.nombre" />
                </div>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>
