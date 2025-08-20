
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue';
    import { notify } from '@/utils/notify';

    const props = defineProps({
        ventas: Array,
        cliente: Object,
        abonos: Array,
    });

    const form = useForm({
        nombre: props.cliente !== undefined ? props.cliente.nombre : '',
        id: props.cliente !== undefined ? props.cliente.id: null,
        abono: 0
    });

    const abonarNota = () => {
        if (form.abono > 0) {
            form.put(route('venta.update', props.cliente.id), form);
        } else {
            notify('No puedes abonar 0 pesos a una nota', 'error');
        }
    }

</script>

<template>
    <AppLayout title="Creditos">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Creditos del cliente: {{ props.cliente.nombre }}
            </h2>
        </template>

        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <div v-if="props.ventas.data.length !== 0">
                            <label class="block font-medium text-sm text-gray-700">Abonar a cuenta</label>
                            <input type="number" step="0.01"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.abono">
                            <br>
                            <br>
                            <button @click="abonarNota()"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                            hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                            focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                            dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Abonar a nota
                            </button>
                            <hr class="my-6">
                        </div>

                        <div style="text-align: center">
                            <label><strong>Balance del credito</strong></label>
                        </div>
                        <br>

                        <div style="display: flex; justify-content: space-around;">
                            <span> Tiene un credito total: {{ cliente.adeudo_total }} </span>
                            <span> Tiene un total de abonos: {{ cliente.abono_total }}</span>
                            <span> Tiene un adeudo total: {{ cliente.balance }}</span>
                        </div>
                        <br>

                        <hr class="my-6">

                        <div class="flex flex-wrap">
                            <div class="relative flex flex-col min-w-0 break-words bg-white w-full mb-6 shadow-lg rounded">
                                <div class="px-4 py-5 flex-auto">
                                    <div style="text-align: center">
                                        <label><strong>Notas de venta</strong></label>
                                    </div>
                                    <br>
                                    <div class="tab-content tab-space">
                                        <div>
                                            <div v-for="venta in props.ventas.data" :key="venta.id">
                                                <div style=" text-align: center"><strong>Nota numero {{ venta.id }}</strong></div>
                                                <br>
                                                <label class="block font-medium text-sm text-gray-700">Venta numero: {{ venta.id }}</label>
                                                <br>
                                                <label class="block font-medium text-sm text-gray-700">Fecha de venta: {{ venta.created_at }}</label>
                                                <br>
                                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                                        <tr>
                                                        <th>Id</th>
                                                        <th>Nombre</th>
                                                        <th>Cantidad</th>
                                                        <th>Importe</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="product in venta.productos" :key="product.id">
                                                            <td class="px-4 py-2"> {{ product.id }}</td>
                                                            <td class="px-4 py-2"> {{ product.detalle }}</td>
                                                            <td class="px-4 py-2"> {{ product.cantidad }}</td>
                                                            <td class="px-4 py-2"> {{ product.total_productos }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <br>
                                                <label class="block font-medium text-sm text-gray-700">Precio total: {{ venta.total }}</label>
                                                <hr class="my-6">

                                            </div>
                                            <div name="Pagination">
                                                <Pagination class="mt-6" :links="props.ventas.links" :prefix="''" />
                                            </div>
                                        </div>
                                        <div>
                                            <div style="text-align: center">
                                                <h2><strong>Abonos a notas</strong></h2>
                                            </div>
                                            <br>
                                            <div id="abonos" v-for="abono in props.abonos.data" :key="abono.id">
                                                El cliente {{ cliente.nombre}} abonó la cantidad  de ${{ abono.cantidad_abonada }} con fecha {{ abono.created_at }}
                                                <hr class="my-6">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
