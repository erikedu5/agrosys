
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
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
    <AppLayout title="CrearEnfermedad">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Enfermedad
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
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Descripción</label>
                            <textarea
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.descripcion"
                                rows="8">
                            </textarea>
                            <InputError class="mt-2" :message="form.errors.descripcion" />
                            <br>
                            <br>

                            <button class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
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
