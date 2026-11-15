<template>
    <AppLayout title="Tipo de flor">
        <template #header>
            <PageHeader :title="tipoFlor ? 'Editar tipo de flor' : 'Nuevo tipo de flor'" subtitle="Catálogo de tipos de flor" :back="route('tipoFlor.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="ui-card flex flex-col gap-4 p-4 sm:p-5" @submit.prevent="submit">
                <div class="ui-field">
                    <label class="ui-label">Nombre</label>
                    <input type="text" class="ui-input" v-model="form.nombre">
                    <InputError :message="form.errors.nombre" />
                </div>

                <div class="ui-field">
                    <label class="ui-label">Enfermedades</label>
                    <vue-single-select v-model="enfermedadSeleccionada" :options="enfermedades" option-key="id" option-label="nombre" placeholder="Seleccione una enfermedad" @input="agregarEnfermedad" class="w-full" />
                    <div v-if="form.selectedOptions.length" class="flex flex-wrap gap-2 pt-1">
                        <span v-for="(e, idx) in form.selectedOptions" :key="idx" class="inline-flex h-9 items-center gap-1 rounded-full border border-gray-300 bg-white pl-3 pr-1 text-sm font-semibold text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                            {{ e }}
                            <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-red-700 dark:hover:bg-gray-700" :aria-label="`Quitar ${e}`" @click="form.selectedOptions.splice(idx,1)">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </span>
                    </div>
                    <InputError :message="form.errors.selectedOptions" />
                </div>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing">Guardar</button>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { usePersistedForm } from '@/stores/formStore';
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';


const props=defineProps({
    tipoFlor: Object,
    enfermedades: Array,
    enfermedadesSelected: Array,
});

const { form, reset } = usePersistedForm('tipoFlorForm', {
    nombre: props.tipoFlor !== undefined ? props.tipoFlor.nombre : '',
    id: props.tipoFlor !== undefined ? props.tipoFlor.id: null,
    selectedOptions: props.tipoFlor !== undefined ? props.tipoFlor.selectedOptions : [],
});

const enfermedadSeleccionada = ref(null);
const agregarEnfermedad = (e) => {
    if (e && !form.selectedOptions.includes(e.nombre)) {
        form.selectedOptions.push(e.nombre);
    }
    enfermedadSeleccionada.value = null;
};

const submit = () => {
    if (props.tipoFlor == undefined) {
        form.post(route('tipoFlor.store'), {
            onSuccess: reset,
        });
    } else {
        form.put(route('tipoFlor.update', props.tipoFlor.id), {
            onSuccess: reset,
        });
    }
}
</script>
