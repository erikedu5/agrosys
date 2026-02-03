<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import VueDatePicker from '@vuepic/vue-datepicker';
    import '@vuepic/vue-datepicker/dist/main.css';
    import { ref, watch } from 'vue';
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';

    const props = defineProps({
        clasificaciones: Array,
        marcas: Array,
        productos: Array,
        sucursales: Array,
    });

    const errors = ref([]);

    const cardClass = 'rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800';
    const sectionTitleClass = 'text-lg font-semibold text-gray-900 dark:text-gray-100';
    const sectionDescClass = 'text-sm text-gray-600 dark:text-gray-300';
    const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-200';
    const primaryButtonClass = 'inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-400/50';
    const helperTextClass = 'text-xs text-gray-500 dark:text-gray-400';

    const formReporte = useForm({
        datesReport: [],
        fechaInicio: {
            type: Date
        },
        fechaFin: {
            type: Date
        },
        datesReportVenta: [],
        datesReportProfit: [],
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
        if (!formReporte.datesReport || formReporte.datesReport.length !== 2 || !formReporte.datesReport[0] || !formReporte.datesReport[1]) {
            errors.value.push('Debe seleccionar un rango de fechas válido (YYYY-MM-DD).');
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
        if (!formReporte.datesReportVenta || formReporte.datesReportVenta.length !== 2 || !formReporte.datesReportVenta[0] || !formReporte.datesReportVenta[1]) {
            errors.value.push('Debe seleccionar un rango de fechas válido (YYYY-MM-DD).');
            return;
        }
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

    const generarReporteGanancias = () => {
        errors.value = [];
        if (!formReporte.datesReportProfit || formReporte.datesReportProfit.length !== 2 || !formReporte.datesReportProfit[0] || !formReporte.datesReportProfit[1]) {
            errors.value.push('Debe seleccionar un rango de fechas válido (YYYY-MM-DD) para el reporte de ganancias.');
            return;
        }

        const fechaInicio = formReporte.datesReportProfit[0];
        const fechaFin = formReporte.datesReportProfit[1];
        let popup  = window.open( "_blank");
        const sucursalQuery = formReporte.id_sucursal ? '&id_sucursal=' + formReporte.id_sucursal : '';
        popup.location = '/reporte/ganancias-diarias?fechaInicio=' + fechaInicio + '&fechaFin=' + fechaFin + sucursalQuery;
        location.replace('/reporte');
    }

    const generarReporteClientesAdeudo = () => {
        errors.value = [];
        const popup = window.open('_blank');
        popup.location = '/reporte/clientes-adeudo';
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

        <div class="bg-gray-50/60 dark:bg-gray-900/40">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
                <div class="flex flex-col gap-2">
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Centro de reportes</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Genera reportes por sucursal y fechas. Para rangos grandes, algunos reportes se descargan en CSV.
                    </p>
                </div>

                <div v-if="errors.length" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/30">
                    <p class="text-sm font-semibold text-red-700 dark:text-red-200">Revisa lo siguiente:</p>
                    <ul class="mt-2 list-disc list-inside text-sm text-red-700 space-y-1 dark:text-red-200">
                        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                    </ul>
                </div>

                <div class="mt-6 grid gap-6">
                    <section
                        v-if="$page.props.auth.user.tipo == 'vendedor' ||
                            $page.props.auth.user.tipo == 'admin' ||
                            $page.props.auth.user.tipo == 'superAdmin' ||
                            $page.props.auth.user.tipo == 'adminEmpresa'"
                        :class="cardClass"
                        id="ventas"
                    >
                        <div class="flex flex-col gap-1">
                            <h3 :class="sectionTitleClass">Reporte de ventas</h3>
                            <p :class="sectionDescClass">Detalle por producto, cliente y stock.</p>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div v-if="props.sucursales.length > 0">
                                <label :class="labelClass">Sucursal</label>
                                <vue-single-select v-model="sucursalSeleccionada" :options="props.sucursales" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            </div>
                            <div>
                                <label :class="labelClass">Rango de fechas</label>
                                <VueDatePicker
                                    v-model="formReporte.datesReport"
                                    range
                                    model-type="yyyy-MM-dd"
                                    format="yyyy-MM-dd"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <p :class="helperTextClass">Si el rango es muy grande, se descargará un CSV.</p>
                            <button @click="generarReporteVentas()" :class="primaryButtonClass">Generar reporte</button>
                        </div>
                    </section>

                    <section
                        v-if="$page.props.auth.user.tipo == 'vendedor' ||
                            $page.props.auth.user.tipo == 'admin' ||
                            $page.props.auth.user.tipo == 'superAdmin' ||
                            $page.props.auth.user.tipo == 'adminEmpresa'"
                        :class="cardClass"
                        id="pormarca"
                    >
                        <div class="flex flex-col gap-1">
                            <h3 :class="sectionTitleClass">Ventas por marca o producto</h3>
                            <p :class="sectionDescClass">Filtra por marca o por producto para un análisis más puntual.</p>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div v-if="props.sucursales.length > 0">
                                <label :class="labelClass">Sucursal</label>
                                <vue-single-select v-model="sucursalSeleccionada" :options="props.sucursales" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            </div>
                            <div>
                                <label :class="labelClass">Rango de fechas</label>
                                <VueDatePicker
                                    v-model="formReporte.datesReportVenta"
                                    range
                                    model-type="yyyy-MM-dd"
                                    format="yyyy-MM-dd"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <label :class="labelClass">Tipo de reporte</label>
                                <vue-single-select v-model="tipoReporteVentaSeleccionado" :options="tipoReporteVentaOptions" option-key="value" option-label="label" class="w-full" />
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div v-if="formReporte.tipoReporteVenta == 'marca'">
                                <label :class="labelClass">Marca</label>
                                <vue-single-select v-model="marcaVentaSeleccionada" :options="marcas" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            </div>
                            <div v-if="formReporte.tipoReporteVenta == 'producto'">
                                <label :class="labelClass">Producto</label>
                                <vue-single-select v-model="productoSeleccionado" :options="productos" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button @click="generarReporteVentasProducto()" :class="primaryButtonClass">Generar reporte</button>
                        </div>
                    </section>

                    <section
                        v-if="$page.props.auth.user.tipo == 'inventario' ||
                            $page.props.auth.user.tipo == 'admin' ||
                            $page.props.auth.user.tipo == 'superAdmin' ||
                            $page.props.auth.user.tipo == 'adminEmpresa'"
                        :class="cardClass"
                        id="inventario"
                    >
                        <div class="flex flex-col gap-1">
                            <h3 :class="sectionTitleClass">Reporte de inventario</h3>
                            <p :class="sectionDescClass">Consulta existencias por sucursal, clasificación y marca.</p>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div v-if="props.sucursales.length > 0">
                                <label :class="labelClass">Sucursal</label>
                                <vue-single-select v-model="sucursalSeleccionada" :options="props.sucursales" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            </div>
                            <div>
                                <label :class="labelClass">Clasificación (opcional)</label>
                                <vue-single-select v-model="clasificacionSeleccionada" :options="clasificaciones" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            </div>
                            <div>
                                <label :class="labelClass">Marca (opcional)</label>
                                <vue-single-select v-model="marcaSeleccionada" :options="marcas" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button @click="generarReporteInventario()" :class="primaryButtonClass">Generar reporte</button>
                        </div>
                    </section>

                    <section
                        v-if="$page.props.auth.user.tipo == 'vendedor' ||
                            $page.props.auth.user.tipo == 'admin' ||
                            $page.props.auth.user.tipo == 'superAdmin' ||
                            $page.props.auth.user.tipo == 'adminEmpresa'"
                        :class="cardClass"
                    >
                        <div class="flex flex-col gap-1">
                            <h3 :class="sectionTitleClass">Clientes con adeudo</h3>
                            <p :class="sectionDescClass">Listado de clientes con saldo pendiente agrupado por sucursal.</p>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button @click="generarReporteClientesAdeudo()" :class="primaryButtonClass">Generar reporte</button>
                        </div>
                    </section>

                    <section
                        v-if="$page.props.auth.user.tipo == 'adminEmpresa' || $page.props.auth.user.tipo == 'superAdmin'"
                        :class="cardClass"
                    >
                        <div class="flex flex-col gap-1">
                            <h3 :class="sectionTitleClass">Ganancias diarias</h3>
                            <p :class="sectionDescClass">Disponible para administración de empresa.</p>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div v-if="props.sucursales.length > 0">
                                <label :class="labelClass">Sucursal</label>
                                <vue-single-select v-model="sucursalSeleccionada" :options="props.sucursales" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            </div>
                            <div>
                                <label :class="labelClass">Rango de fechas</label>
                                <VueDatePicker
                                    v-model="formReporte.datesReportProfit"
                                    range
                                    model-type="yyyy-MM-dd"
                                    format="yyyy-MM-dd"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button @click="generarReporteGanancias()" :class="primaryButtonClass">Generar reporte</button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
