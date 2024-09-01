<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch } from 'vue';
    import { router, Link, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue'
    import VueDatePicker from '@vuepic/vue-datepicker';
    import '@vuepic/vue-datepicker/dist/main.css';

    defineProps({
        facturas: {
            type: Array,
            default: []
        }
    });

    const form = useForm({
        dates: []
    });

    const minusDays = (date, days) => {
        let result = new Date(date);
        result.setDate(date.getDate() - days);
        return result;
    }

    let fechaFin = new Date();
    let fechaInicio = minusDays(fechaFin, 1);

    const filterFacturas = () => {
        const InicioString = form.dates[0].split('-')
        const FinString = form.dates[1].split('-')
        fechaInicio = new Date(InicioString[2], parseInt(InicioString[1]) - 1, InicioString[0]);
        fechaFin = new Date(FinString[2], parseInt(FinString[1]) - 1, FinString[0]);
        router.get( route( 'facturas.index', { 'fechaInicio': fechaInicio, 'fechaFin': fechaFin } ), { preserveState: true } );
    }

    const update = (id) => {
        router.put(route('facturas.update', { 'id': id, 'fechaInicio': fechaInicio, 'fechaFin': fechaFin }));
    }

</script>

<template>
    <AppLayout title="Facturas">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Facturas
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">

                        <div class="flex justify-between">
                            <VueDatePicker v-model="form.dates" range  model-type="dd-MM-yyyy"></VueDatePicker>
                            <button @click="filterFacturas"
                                  class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                filtar
                            </button>
                        </div>

                        <hr class="my-6">

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                    <th>Id</th>
                                    <th>Nombre del cliente</th>
                                    <th>RFC</th>
                                    <th>Id venta</th>
                                    <th>Fecha Venta</th>
                                    <th>Cantidad a Facturar</th>
                                    <th>Ha sido facturada</th>
                                    <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="factura in facturas.data" :key="factura.id">
                                        <td class="px-4 py-2"> {{ factura.id }}</td>
                                        <td class="px-4 py-2"> {{ factura.cliente.nombre }} </td>
                                        <td class="px-4 py-2"> {{ factura.cliente.rfc }} </td>
                                        <td class="px-4 py-2"> {{ factura.venta.id }} </td>
                                        <td class="px-4 py-2"> {{ factura.venta.created_at }} </td>
                                        <td class="px-4 py-2"> $ {{ factura.venta.total }} </td>
                                        <td class="px-4 py-2"> {{ factura.facturaCompleta ? 'Facturada': 'No Facturada' }} </td>
                                        <td class="px-4 py-2">
                                            <div class="inline-flex rounded-md shadow-sm" role="group">
                                                <button @click="update(factura.id)" v-show="!factura.facturaCompleta"
                                                 class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200
                                                        rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2
                                                        focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600
                                                        dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500
                                                         dark:focus:text-white">
                                                    Marcada como facturada
                                            </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div name="Pagination">
                            <Pagination class="mt-6" :links="facturas.links" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-none w-14 h-14">
            </div>
        </div>
    </AppLayout>
</template>
