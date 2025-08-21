
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({empresa: Object});

    const { form, reset } = usePersistedForm('empresaForm', {
        id: props.empresa !== undefined ? props.empresa.id: null,
        nombre: props.empresa !== undefined ? props.empresa.nombre : '',
        direccion: props.empresa !== undefined ? props.empresa.direccion : '',
        telefono: props.empresa !== undefined ? props.empresa.telefono : '',
        email: props.empresa !== undefined ? props.empresa.email : '',
        rfc: props.empresa !== undefined ? props.empresa.rfc : '',
        aviso: props.empresa !== undefined ? props.empresa.aviso : '',
        numero_sucursales: props.empresa !== undefined ? props.empresa.numero_sucursales: 1,
    });

    const submit = () => {
        if (props.empresa == null) {
            form.post(route('empresa.store'), {
                onSuccess: reset,
            });
        } else {
            form.put(route('empresa.update', props.empresa.id), {
                onSuccess: reset,
            });
        }
    }
</script>

<template>
    <AppLayout title="CrearEmpresa">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Empresa
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-5">
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

                            <label class="block font-medium text-sm text-gray-700">Dirección</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.direccion">
                            <InputError class="mt-2" :message="form.errors.direccion" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Telefono</label>
                            <input type="tel"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.telefono">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Email</label>
                            <input type="email"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.email">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">RFC</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.rfc">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Aviso</label>
                            <textarea
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.aviso"
                                rows="6">
                            </textarea>
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Máximo número de sucursales</label>
                            <input type="number" step="0.01"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.numero_sucursales">
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
