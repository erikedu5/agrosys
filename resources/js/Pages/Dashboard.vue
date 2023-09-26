<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch } from 'vue';
    import { router, Link, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue';
    import VueDatePicker from '@vuepic/vue-datepicker';
    import '@vuepic/vue-datepicker/dist/main.css';

    defineProps({
        solucionesByProduct: {
            type: Array,
            default: []
        }
    })
    const formReporte = useForm({
        datesReport: []
    });

    const q = ref('');

    watch(q, (value) => {
        router.get( route( 'dashboard', { q: value } ), {}, { preserveState: true } );
    });

    const generarReporteVentas = () => {
        let fechaInicio = formReporte.datesReport[0];
        let fechaFin = formReporte.datesReport[1];

    }

</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Barra de busqueda de agroquimícos
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
                            <input type="text" class="form-input rounded-md shadow-sm w-5/6" v-model="q" placeholder="Buscar Producto...">
                        </div>
                        <br>
                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                    <th>Nombre del producto</th>
                                    <th>Enfermedad en planta</th>
                                    <th>Ingrediente activo</th>
                                    <th>Dosis en ml por bomba</th>
                                    <th>Dosis en ml por tambo</th>
                                    <th>Cantidad en stock</th>
                                    <th>Ultima actualización del stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="solucionByProduct in solucionesByProduct.data"  :value="solucionByProduct.producto.id" :key="solucionByProduct.producto.id" >
                                        <td class="px-4 py-2"> {{ solucionByProduct.producto.nombre }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.enfermedad.nombre }} en {{ solucionByProduct.tipoFlor.nombre }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.producto.ingrediente_activo }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.dosis_bomba_ml }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.dosis_tambo_ml }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.producto.cantidad }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.producto.updated_at }} </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div name="Pagination">
                                <Pagination class="mt-6" :links="solucionesByProduct.links" :prefix="''" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-none w-14 h-14">
            </div>
        </div>
    </AppLayout>
</template>
