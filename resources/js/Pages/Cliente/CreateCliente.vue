
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';

    const props=defineProps({cliente: Object});

    const form = useForm({
        requiereFactura: props.cliente !== undefined ? props.cliente.requiereFactura : 0,
        rfc: props.cliente !== undefined ? props.cliente.rfc : '',
        nombre: props.cliente !== undefined ? props.cliente.nombre : '',
        porcentaje_descuento: props.cliente !== undefined ? props.cliente.porcentaje_descuento: 0,
        id: props.cliente !== undefined ? props.cliente.id: null,
    });

    const submit = () => {
        if (props.cliente == undefined) {
            form.post(route('cliente.store'), form);
        } else {
            form.put(route('cliente.update', props.cliente.id), form);
        }
    }
</script>

<template>
    <AppLayout title="CrearCliente">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Crear Cliente
            </h2>
        </template>

        <div class="flex">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Nombre</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.nombre">
                            <br>
                            <div v-if="$page.props.errors.nombre" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline"> {{ $page.props.errors.nombre }}</span>
                            </div>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Porcentaje de Descuento</label>
                            <input
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.porcentaje_descuento">
                            <br>
                            <div v-if="$page.props.errors.porcentaje_descuento" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline"> {{ $page.props.errors.porcentaje_descuento }}</span>
                            </div>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Requiere factura</label>
                            <select v-model="form.requiereFactura" id="ieps" name="ieps"
                             @change="calcularIps()"
                             class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="" disabled>Selecione</option>
                            <option value="1">Si</option>
                            <option value="0">No</option>
                            </select>

                            <div v-if="$page.props.errors.requiereFactura" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <br>
                                <span class="block sm:inline"> {{ $page.props.errors.requiereFactura }}</span>
                            </div>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">RFC</label>
                            <input
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.rfc">
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
