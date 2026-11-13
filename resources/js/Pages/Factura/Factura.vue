<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { etiquetaFormaPago } from '@/utils/formasPago';

const props = defineProps({
    facturas: {
        type: Object,
        default: {}
    },
    // Rango aplicado por el servidor, en formato yyyy-MM-dd.
    filtros: {
        type: Object,
        default: () => ({ fechaInicio: null, fechaFin: null })
    }
});

// El selector trabaja en yyyy-MM-dd, igual que el servidor, y arranca con el rango que se está mostrando.
const fechas = ref(props.filtros.fechaInicio && props.filtros.fechaFin ? [props.filtros.fechaInicio, props.filtros.fechaFin] : []);
watch(() => props.filtros, (filtros) => {
    if (filtros.fechaInicio && filtros.fechaFin) fechas.value = [filtros.fechaInicio, filtros.fechaFin];
});

const rangoActual = () => {
    const [inicio, fin] = fechas.value ?? [];
    return {
        fechaInicio: inicio || props.filtros.fechaInicio,
        fechaFin: fin || inicio || props.filtros.fechaFin,
    };
};

const filterFacturas = () => {
    router.get(route('facturas.index'), rangoActual(), { preserveState: true, preserveScroll: true });
};

const descargarExcel = () => {
    window.location.href = route('facturas.export', rangoActual());
};

const update = (id) => {
    router.put(route('facturas.update', { id, ...rangoActual() }), {}, { preserveScroll: true });
};

const estatus = (factura) => {
    if (factura.facturaCompleta) return 'Facturada';
    if (factura.factura_status === 'error') return 'Error al facturar';
    return 'No facturada';
};

</script>

<template>
    <AppLayout title="Facturas">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Facturas
            </h2>
            <br>
            <div class="flex justify-between">
                <VueDatePicker v-model="fechas" range model-type="yyyy-MM-dd" format="dd/MM/yyyy" :clearable="false"></VueDatePicker>
                <button @click="filterFacturas"
                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                    Filtrar
                </button>
                <button @click="descargarExcel"
                    class="ml-2 px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 whitespace-nowrap">
                    Descargar Excel
                </button>
            </div>
        </template>

        <hr class="my-6">
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-2">
            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="factura in facturas.data" :key="factura.id" class="rounded-lg boder p-4 bg-white shadow-lg">
                    <div class="text-sm text-gray-500">Nombre del cliente</div>
                    <div class="font-semibold text-gray-900">{{ factura.cliente.nombre }}</div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">RFC</div>
                            <div>{{ factura.cliente.rfc }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Id venta</div>
                            <div>{{ factura.venta.id }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Fecha Venta</div>
                            <div>{{ factura.venta.created_at }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Cantidad a Facturar</div>
                            <div>$ {{ factura.venta.total }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Forma de pago</div>
                            <div>{{ etiquetaFormaPago(factura.venta.forma_pago) || 'Sin definir' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Estatus</div>
                            <div>{{ estatus(factura) }}</div>
                            <div v-if="factura.factura_error && !factura.facturaCompleta" class="text-xs text-red-600">{{ factura.factura_error }}</div>
                        </div>
                    </div>
                    <div class="flex justify-end mt-3">
                        <div class="inline-flex rounded-md shadow-sm" role="group">
                            <button @click="update(factura.id)" v-show="!factura.facturaCompleta" class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200
                                                        rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2
                                                        focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600
                                                        dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500
                                                         dark:focus:text-white">
                                Marcada como facturada
                            </button>
                        </div>
                    </div>
                </div>
                <p v-if="!facturas.data?.length" class="py-6 text-center text-gray-500">No hay ventas por facturar en el rango seleccionado.</p>
                <div name="Pagination">
                    <Pagination class="mt-6" :links="facturas.links" />
                </div>
            </div>

            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id</th>
                            <th>Nombre del cliente</th>
                            <th>RFC</th>
                            <th>Id venta</th>
                            <th>Fecha Venta</th>
                            <th>Cantidad a Facturar</th>
                            <th>Forma de pago</th>
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
                            <td class="px-4 py-2"> {{ etiquetaFormaPago(factura.venta.forma_pago) || 'Sin definir' }} </td>
                            <td class="px-4 py-2"> {{ estatus(factura) }}
                                <div v-if="factura.factura_error && !factura.facturaCompleta" class="text-xs text-red-600">{{ factura.factura_error }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div class="inline-flex rounded-md shadow-sm" role="group">
                                    <button @click="update(factura.id)" v-show="!factura.facturaCompleta" class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200
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
                <p v-if="!facturas.data?.length" class="py-6 text-center text-gray-500">No hay ventas por facturar en el rango seleccionado.</p>

                <div name="Pagination">
                    <Pagination class="mt-6" :links="facturas.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
