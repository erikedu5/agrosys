
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({
        sucursal: Object,
        empresas: {
            type: Array,
            default: []
        }
    });

    const form = useForm({
        id: props.sucursal !== undefined ? props.sucursal.id: null,
        nombre: props.sucursal !== undefined ? props.sucursal.nombre: '',
        direccion: props.sucursal !== undefined ? props.sucursal.direccion: '',
        telefono: props.sucursal !== undefined ? props.sucursal.telefono: '',
        email: props.sucursal !== undefined ? props.sucursal.email: '',
        es_matriz: props.sucursal !== undefined ? props.sucursal.es_matriz? true: false : false,
        id_empresa: props.sucursal !== undefined ? props.sucursal.id_empresa : props.empresas[0].id,
        ticket_width_mm: props.sucursal !== undefined && props.sucursal.ticket_width_mm ? props.sucursal.ticket_width_mm : 80,
});

    const submit = () => {
        if (props.sucursal == undefined) {
            form.post(route('sucursal.store'), form);
        } else {
            form.put(route('sucursal.update', props.sucursal.id), form);
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

        <div class="flex">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">

                            <label class="block font-medium text-sm text-gray-700">Empresa</label>
                            <select v-model="form.id_empresa" id="empresa" name="empresa"
                             class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option v-for="empresa in empresas" :value="empresa.id" :key="empresa.id">
                                {{ empresa.nombre }}
                            </option>
                            </select>
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

                            <label class="block font-medium text-sm text-gray-700">Ancho de Ticket (mm)</label>
                            <select v-model.number="form.ticket_width_mm" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option :value="80">80 mm (recomendado)</option>
                                <option :value="58">58 mm</option>
                            </select>
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
            <div class="flex-none w-14 h-14">
            </div>
        </div>
    </AppLayout>
</template>
