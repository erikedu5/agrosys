<template>
    <AppLayout title="CrearTipoFlor">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Tipo de flor
            </h2>
        </template>
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8  mt-5">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Nombre</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.nombre">
                            <InputError class="mt-2" :message="form.errors.nombre" />
                                <br>
                                <label class="block font-medium text-sm text-gray-700">Enfermedades</label>
                                <vue-single-select v-model="enfermedadSeleccionada" :options="enfermedades" option-key="id" option-label="nombre" placeholder="Seleccione una enfermedad" @input="agregarEnfermedad" class="w-full" />
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span v-for="(e, idx) in form.selectedOptions" :key="idx" class="bg-gray-200 px-2 py-1 rounded">
                                        {{ e }}
                                        <button type="button" class="ml-1" @click="form.selectedOptions.splice(idx,1)">x</button>
                                    </span>
                                </div>
                                <InputError class="mt-2" :message="form.errors.selectedOptions" />
                                <br>
                            <button
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Guardar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
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
