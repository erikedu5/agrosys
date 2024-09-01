
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import Checkbox from '@/Components/Checkbox.vue';

    const props=defineProps({cliente: Object});

    const form = useForm({
        nombre: props.cliente !== undefined ? props.cliente.nombre : '',
        requiereFactura: props.cliente !== undefined ? props.cliente.requiereFactura: false,
        rfc: props.cliente !== undefined ? props.cliente.rfc: null,
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
                            <input type="text" required
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.nombre">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">porcentaje de Descuento</label>
                            <input type="number"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.porcentaje_descuento">
                            <br>
                            <br>
                            <label class="block font-medium text-sm text-gray-700">
                                <checkbox v-model="form.requiereFactura" value="false" />
                                <span class="ml-2 text-sm">Require factura</span>
                            </label><br>

                            <label class="block font-medium text-sm text-gray-700">RFC</label>
                            <input :required="form.requiereFactura"
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
