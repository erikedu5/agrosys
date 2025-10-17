
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';

const props = defineProps({
    empresa: Object,
    all: {
        type: Boolean,
        default: false
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    }
});

    const { form, reset } = usePersistedForm('empresaForm', {
        id: props.empresa !== undefined ? props.empresa.id: null,
        nombre: props.empresa !== undefined ? props.empresa.nombre : '',
        direccion: props.empresa !== undefined ? props.empresa.direccion : '',
        telefono: props.empresa !== undefined ? props.empresa.telefono : '',
        email: props.empresa !== undefined ? props.empresa.email : '',
        rfc: props.empresa !== undefined ? props.empresa.rfc : '',
        aviso: props.empresa !== undefined ? props.empresa.aviso : '',
        numero_sucursales: props.empresa !== undefined ? props.empresa.numero_sucursales: 1,
        mostrar_campos_precio: props.empresa !== undefined
            ? Boolean(props.empresa.mostrar_campos_precio)
            : true,
        ventas_bloqueadas: props.empresa !== undefined
            ? Boolean(props.empresa.ventas_bloqueadas)
            : false,
        motivo_bloqueo: props.empresa !== undefined && props.empresa.motivo_bloqueo
            ? props.empresa.motivo_bloqueo
            : '',
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

                            <label class="inline-flex items-center">
                                <input type="checkbox"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    v-model="form.mostrar_campos_precio">
                                <span class="ml-2 text-sm text-gray-700">
                                    Mostrar precio de compra y porcentaje de ganancia en productos
                                </span>
                            </label>
                            <br>
                            <br>
                            <div v-if="isSuperAdmin">
                                <label class="inline-flex items-center">
                                    <input type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                        v-model="form.ventas_bloqueadas">
                                    <span class="ml-2 text-sm text-gray-700">
                                        Esta sección está bloqueada, Contacte a su administrador
                                    </span>
                                </label>
                                <InputError class="mt-2" :message="form.errors.ventas_bloqueadas" />
                                <br>
                                <br>
                                <div>
                                    <label class="block font-medium text-sm text-gray-700">
                                        Motivo del bloqueo
                                    </label>
                                    <textarea
                                        class="form-input w-full rounded-md shadow-sm"
                                        :class="{ 'bg-gray-100 cursor-not-allowed': !form.ventas_bloqueadas }"
                                        rows="3"
                                        v-model="form.motivo_bloqueo"
                                        :disabled="!form.ventas_bloqueadas"
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.motivo_bloqueo" />
                                </div>
                                <br>
                                <br>
                            </div>

                            <div v-if="!all">
                            <label class="block font-medium text-sm text-gray-700">Máximo número de sucursales</label>
                            <input type="number" step="0.01"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.numero_sucursales">
                            <br>
                            <br>
                            </div>

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
