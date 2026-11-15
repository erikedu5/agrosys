
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({enfermedad: Object});

    const { form, reset } = usePersistedForm('enfermedadForm', {
        nombre: props.enfermedad !== undefined ? props.enfermedad.nombre : '',
        descripcion: props.enfermedad !== undefined ? props.enfermedad.descripcion: '',
        id: props.enfermedad !== undefined ? props.enfermedad.id: null,
    });

    const submit = () => {
        if (props.enfermedad == undefined) {
            form.post(route('enfermedad.store'), {
                onSuccess: reset,
            });
        } else {
            form.put(route('enfermedad.update', props.enfermedad.id), {
                onSuccess: reset,
            });
        }
    }
</script>

<template>
    <AppLayout title="Enfermedad">
        <template #header>
            <PageHeader :title="enfermedad ? 'Editar enfermedad' : 'Nueva enfermedad'" subtitle="Catálogo de enfermedades" :back="route('enfermedad.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="ui-card flex flex-col gap-4 p-4 sm:p-5" @submit.prevent="submit">
                <div class="ui-field">
                    <label class="ui-label">Nombre</label>
                    <input type="text" class="ui-input" v-model="form.nombre">
                    <InputError :message="form.errors.nombre" />
                </div>

                <div class="ui-field">
                    <label class="ui-label">Descripción</label>
                    <textarea class="ui-input" v-model="form.descripcion" rows="8"></textarea>
                    <InputError :message="form.errors.descripcion" />
                </div>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>
