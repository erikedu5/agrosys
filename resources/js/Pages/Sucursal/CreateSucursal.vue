
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import { usePersistedForm } from '@/stores/formStore';
    import InputError from '@/Components/InputError.vue';
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';
    import { ref, watch } from 'vue';

    const props=defineProps({
        sucursal: Object,
        empresas: {
            type: Array,
            default: []
        }
    });

    const { form, reset } = usePersistedForm('sucursalForm', {
        id: props.sucursal !== undefined ? props.sucursal.id: null,
        nombre: props.sucursal !== undefined ? props.sucursal.nombre: '',
        direccion: props.sucursal !== undefined ? props.sucursal.direccion: '',
        telefono: props.sucursal !== undefined ? props.sucursal.telefono: '',
        email: props.sucursal !== undefined ? props.sucursal.email: '',
        es_matriz: props.sucursal !== undefined ? props.sucursal.es_matriz? true: false : false,
        es_bodega: props.sucursal !== undefined ? props.sucursal.es_bodega? true: false : false,
        id_empresa: props.sucursal !== undefined ? props.sucursal.id_empresa : props.empresas[0].id,
        ticket_width_mm: props.sucursal !== undefined && props.sucursal.ticket_width_mm ? props.sucursal.ticket_width_mm : 80,
});

    const empresaSeleccionada = ref(props.empresas.find(e => e.id === form.id_empresa) || null);
    watch(empresaSeleccionada, (val) => {
        form.id_empresa = val ? val.id : null;
    });

    const ticketOptions = [
        { value: 80, label: '80 mm (recomendado)' },
        { value: 58, label: '58 mm' }
    ];
    const ticketSeleccionado = ref(ticketOptions.find(t => t.value === form.ticket_width_mm) || null);
    watch(ticketSeleccionado, (val) => {
        form.ticket_width_mm = val ? val.value : null;
    });

    const submit = () => {
        if (props.sucursal == undefined) {
            form.post(route('sucursal.store'), {
                onSuccess: reset,
            });
        } else {
            form.put(route('sucursal.update', props.sucursal.id), {
                onSuccess: reset,
            });
        }
    }
</script>
<template>
    <AppLayout title="CrearSucursal">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Sucursales
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-5">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">

                            <label class="block font-medium text-sm text-gray-700">Empresa</label>
                            <vue-single-select v-model="empresaSeleccionada" :options="empresas" option-key="id" option-label="nombre" placeholder="Seleccione una empresa" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.id_empresa" />

                            <br>
                            <br>

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
                            <InputError class="mt-2" :message="form.errors.telefono" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Email</label>
                            <input type="email"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.email">
                            <InputError class="mt-2" :message="form.errors.email" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Es Matriz</label>
                            <input type="checkbox" class="form-input rounded-md shadow-sm"
                                v-model="form.es_matriz">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Es Bodega</label>
                            <input type="checkbox" class="form-input rounded-md shadow-sm"
                                v-model="form.es_bodega">
                                <span class="text-xs text-gray-500 ml-2">(Permite realizar transferencias desde esta ubicación)</span>
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Ancho de Ticket (mm)</label>
                            <vue-single-select v-model="ticketSeleccionado" :options="ticketOptions" option-key="value" option-label="label" placeholder="Seleccione" class="w-full" />
                            <InputError class="mt-2" :message="form.errors.ticket_width_mm" />
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
