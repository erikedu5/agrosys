<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import VueDatePicker from '@vuepic/vue-datepicker';
    import '@vuepic/vue-datepicker/dist/main.css';
    import InputError from '@/Components/InputError.vue';
    import { ref, watch } from 'vue';
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';

    const props = defineProps({
        clasificaciones: Array,
        marcas: Array,
        productos: Array,
        sucursales: Array,
    });

    const errors = ref([]);

    const formReporte = useForm({
        datesReport: [],
        fechaInicio: {
            type: Date
        },
        fechaFin: {
            type: Date
        },
        datesReportVenta: [],
        tipoReporteVenta: 'marca',
        id_clasificacion: 0,
        id_marca:  0,
        id_marca_venta: 0,
        id_producto: 0,
        id_sucursal: 0,
    });

    const sucursalSeleccionada = ref(props.sucursales.find(s => s.id === formReporte.id_sucursal) || null);
    watch(sucursalSeleccionada, (v) => {
        formReporte.id_sucursal = v ? v.id : 0;
    });

    const tipoReporteVentaOptions = [
        { value: 'marca', label: 'marca' },
        { value: 'producto', label: 'producto' }
    ];
    const tipoReporteVentaSeleccionado = ref(tipoReporteVentaOptions.find(o => o.value === formReporte.tipoReporteVenta));
    watch(tipoReporteVentaSeleccionado, (v) => {
        formReporte.tipoReporteVenta = v ? v.value : 'marca';
    });

    const marcaVentaSeleccionada = ref(null);
    watch(marcaVentaSeleccionada, (v) => {
        formReporte.id_marca_venta = v ? v.id : 0;
    });

    const productoSeleccionado = ref(null);
    watch(productoSeleccionado, (v) => {
        formReporte.id_producto = v ? v.id : 0;
    });

    const clasificacionSeleccionada = ref(null);
    watch(clasificacionSeleccionada, (v) => {
        formReporte.id_clasificacion = v ? v.id : 0;
    });

    const marcaSeleccionada = ref(null);
    watch(marcaSeleccionada, (v) => {
        formReporte.id_marca = v ? v.id : 0;
    });

    const generarReporteVentas = () => {
        errors.value = [];
        if (formReporte.datesReport.length == 0) {
            errors.value.push('Debe seleccionar un rango de fecha');
            return;
        }
        let fechaFin = formReporte.datesReport[1];
        let fechaInicio = formReporte.datesReport[0];
        let popup  = window.open( "_blank");
        let sucursalQuery = formReporte.id_sucursal ? '&id_sucursal=' + formReporte.id_sucursal : '';
        popup.location = '/reporte/venta?fechaInicio=' + fechaInicio + '&fechaFin=' + fechaFin + sucursalQuery;
        location.replace('/reporte');

    }

    const generarReporteVentasProducto = () => {
        errors.value = [];
        let id_marca = formReporte.id_marca_venta;
        let id_producto = formReporte.id_producto;

        let fechaFin = formReporte.datesReportVenta[1];
        let fechaInicio = formReporte.datesReportVenta[0];

        let popup  = window.open( "_blank");
        let sucursalQuery = formReporte.id_sucursal ? '&id_sucursal=' + formReporte.id_sucursal : '';
        popup.location = '/reporte/ventaPorProductoMarca?id_marca=' + id_marca + '&id_producto=' + id_producto + '&fechaInicio=' + fechaInicio + '&fechaFin=' + fechaFin + sucursalQuery;

        formReporte.id_marca_venta = 0;
        formReporte.id_producto = 0;

        location.replace('/reporte');
    }

    const generarReporteInventario = () => {
        errors.value = [];
        let query = "?";
        if (formReporte.id_clasificacion !== 0) {
            query = query + "id_clasificacion=" + formReporte.id_clasificacion + "&";
        }
        if (formReporte.id_marca !== 0) {
            query = query + "id_marca=" + formReporte.id_marca + "&";
        }
        let popup  = window.open( "_blank");
        if (formReporte.id_sucursal) {
            query = query + 'id_sucursal=' + formReporte.id_sucursal + '&';
        }
        popup.location = '/reporte/inventario' + query;
        location.replace('/reporte');
    }

</script>

<template>
    <AppLayout title="Reportes">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Reportes
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grow">
                <div v-if="errors.length">
                    <InputError v-for="(error, index) in errors" :key="index" class="mt-2" :message="error" />
                </div>
                <div class="shadow bg-white md:rounded-md p-4">
                    <div v-if="props.sucursales.length > 0">
                        <label class="block font-medium text-sm text-gray-700">Sucursal</label>
                        <vue-single-select v-model="sucursalSeleccionada" :options="props.sucursales" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                        <br>
                    </div>
                    <div class="md-col-span-2 mt-5 md:mt-0" id="ventas"
                        v-if="$page.props.auth.user.tipo == 'vendedor' ||
                            $page.props.auth.user.tipo == 'admin' ||
                            $page.props.auth.user.tipo == 'superAdmin'" >
                        <label><strong>Reporte de ventas</strong></label>
                        <br>
                        <br>
                        <label>Rango de fechas para generar reporte:</label>
                        <VueDatePicker
                        v-model="formReporte.datesReport"
                        range
                        model-type="dd-MM-yyyy"></VueDatePicker>
                        <br>
                        <button @click="generarReporteVentas()"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                    hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                    focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                    dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Reporte de ventas</button>
                    </div>
                    <hr class="my-6">

                    <div class="md-col-span-2 mt-5 md:mt-0" id="pormarca"
                        v-if="$page.props.auth.user.tipo == 'vendedor' ||
                            $page.props.auth.user.tipo == 'admin' ||
                            $page.props.auth.user.tipo == 'superAdmin'" >
                        <label><strong>Reporte de ventas por marca o producto</strong></label>
                        <br>
                        <br>

                        <div>
                            <label>Rango de fechas para generar reporte:</label>
                            <VueDatePicker
                            v-model="formReporte.datesReportVenta"
                            range
                            model-type="dd-MM-yyyy"></VueDatePicker>
                            <br>
                        </div>


                        <div>
                            <label class="block font-medium text-sm text-gray-700">Marca</label>
                            <vue-single-select v-model="tipoReporteVentaSeleccionado" :options="tipoReporteVentaOptions" option-key="value" option-label="label" class="w-full" />
                            <br>
                        </div>


                        <div v-if="formReporte.tipoReporteVenta == 'marca'">
                            <label class="block font-medium text-sm text-gray-700">Marca</label>
                            <vue-single-select v-model="marcaVentaSeleccionada" :options="marcas" option-key="id" option-label="nombre" placeholder="Selecione" class="w-full" />
                            <br>
                        </div>

                        <div  v-if="formReporte.tipoReporteVenta == 'producto'">
                            <label class="block font-medium text-sm text-gray-700">Producto</label>
                            <vue-single-select v-model="productoSeleccionado" :options="productos" option-key="id" option-label="nombre" placeholder="Selecione" class="w-full" />
                            <br>
                        </div>


                        <button @click="generarReporteVentasProducto()"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                    hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                    focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                    dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Reporte de ventas</button>
                    </div>

                    <hr class="my-6">

                    <div class="md-col-span-2 mt-5 md:mt-0" id="inventario"
                        v-if="$page.props.auth.user.tipo == 'inventario' ||
                            $page.props.auth.user.tipo == 'admin' ||
                            $page.props.auth.user.tipo == 'superAdmin'" >
                        <label><strong>Reporte de inventario</strong></label>
                        <br>
                        <br>
                        <label class="block font-medium text-sm text-gray-700">Clasificacion (Opcional)</label>
                        <vue-single-select v-model="clasificacionSeleccionada" :options="clasificaciones" option-key="id" option-label="nombre" placeholder="Selecione" class="w-full" />
                            <br>

                        <label class="block font-medium text-sm text-gray-700">Marca (Opcional)</label>
                        <vue-single-select v-model="marcaSeleccionada" :options="marcas" option-key="id" option-label="nombre" placeholder="Selecione" class="w-full" />
                            <br>
                        <button @click="generarReporteInventario()"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                    hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                    focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                    dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Reporte de inventario</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



