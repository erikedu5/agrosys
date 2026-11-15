
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';
    import { ref, watch } from 'vue';

    const props=defineProps({clasificacion: Object});

    const { form, reset } = usePersistedForm('clasificacionForm', {
        nombre: props.clasificacion !== undefined ? props.clasificacion.nombre : '',
        id: props.clasificacion !== undefined ? props.clasificacion.id: null,
        criterio: props.clasificacion !== undefined ? props.clasificacion.criterio: '',
    });

    const criterioOptions = [
        { value: 'Función', label: 'Por Función' },
        { value: 'Origen Químico', label: 'Por Origen Químico' },
        { value: 'Persistencia', label: 'Por Persistencia' },
        { value: 'Modo de Accion', label: 'Por Modo de Acción' }
    ];
    const criterioSeleccionado = ref(criterioOptions.find(o => o.value === form.criterio) || null);
    watch(criterioSeleccionado, (v) => {
        form.criterio = v ? v.value : '';
    });

    const submit = () => {
        if (props.clasificacion == undefined) {
            form.post(route('clasificacion.store'), {
                onSuccess: reset,
            });
        } else {
            form.put(route('clasificacion.update', props.clasificacion.id), {
                onSuccess: reset,
            });
        }
    }
</script>

<template>
    <AppLayout title="Clasificación">
        <template #header>
            <PageHeader :title="clasificacion ? 'Editar clasificación' : 'Nueva clasificación'" subtitle="Catálogo de clasificaciones" :back="route('clasificacion.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="ui-card flex flex-col gap-4 p-4 sm:p-5" @submit.prevent="submit">
                <div class="ui-field">
                    <label class="ui-label">Nombre</label>
                    <input type="text" class="ui-input" v-model="form.nombre">
                    <InputError :message="form.errors.nombre" />
                </div>

                <div class="ui-field">
                    <label class="ui-label">Criterio</label>
                    <vue-single-select v-model="criterioSeleccionado" :options="criterioOptions" option-key="value" option-label="label" placeholder="Seleccione" class="w-full" />
                    <InputError :message="form.errors.criterio" />
                </div>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>
