<template>
    <AppLayout title="CrearTipoFlor">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Tipo de flor
            </h2>
        </template>
        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8  mt-5">
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
                                <label class="block font-medium text-sm text-gray-700">Enfermedades (Seleccione más de una con tecla ctrl/command)</label>
                                <select multiple v-model="form.selectedOptions" class="w-full h-64 rounded-md shadow-sm">
                                    <option v-for="enfermedad in enfermedades" :key="enfermedad.id">
                                        {{ enfermedad.nombre }}
                                    </option>
                                </select>
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
import{ useForm }from'@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';


const props=defineProps({
    tipoFlor: Object,
    enfermedades: Array,
    enfermedadesSelected: Array,
});

const form = useForm({
    nombre: props.tipoFlor !== undefined ? props.tipoFlor.nombre : '',
    id: props.tipoFlor !== undefined ? props.tipoFlor.id: null,
    selectedOptions: props.tipoFlor !== undefined ? props.tipoFlor.selectedOptions : null,
});

const submit = () => {
    if (props.tipoFlor == undefined) {
        form.post(route('tipoFlor.store'), form);
    } else {
        form.put(route('tipoFlor.update', props.tipoFlor.id), form);
    }
}
</script>
