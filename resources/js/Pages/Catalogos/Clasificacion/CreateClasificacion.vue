
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({clasificacion: Object});

    const form = useForm({
        nombre: props.clasificacion !== undefined ? props.clasificacion.nombre : '',
        id: props.clasificacion !== undefined ? props.clasificacion.id: null,
        criterio: props.clasificacion !== undefined ? props.clasificacion.criterio: '',
    });

    const submit = () => {
        if (props.clasificacion == undefined) {
            form.post(route('clasificacion.store'), form);
        } else {
            form.put(route('clasificacion.update', props.clasificacion.id), form);
        }
    }
</script>

<template>
    <AppLayout title="CrearClasificacion">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Clasificaciones
            </h2>
        </template>

        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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

                            <label class="block font-medium text-sm text-gray-700">Criterio</label>
                            <select v-model="form.criterio" id="criterio" name="criterio"
                             class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="" disabled>Selecione</option>
                            <option value="Función">Por Función</option>
                            <option value="Origen Químico">Por Origen Químico</option>
                            <option value="Persistencia">Por Persistencia</option>
                            <option value="Modo de Accion">Por Modo de Acción</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.criterio" />
                                <br>
                                <br>
                            <button
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg
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
