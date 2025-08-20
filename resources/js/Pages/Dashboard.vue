<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

defineProps({
    solucionesByProduct: {
        type: Object,
        default: {}
    }
})
const formReporte = useForm({
    datesReport: []
});

const q = ref('');

watch(q, (value) => {
    router.get(route('dashboard', { q: value }), {}, { preserveState: true });
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
            <div class="flex justify-between">
                <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                    placeholder="Buscar Producto...">
            </div>
        </template>

        <hr class="my-6">

        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Vista en tarjetas (mobile) -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4  w-full">
                <div v-for="s in solucionesByProduct.data" :key="s.producto.id"
                    class="rounded-lg border p-4 bg-white shadow-sm">
                    <div class="text-sm text-gray-500">Nombre del producto</div>
                    <div class="font-semibold text-gray-900">{{ s.producto.nombre }}</div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">Enfermedad</div>
                            <div>{{ s.enfermedad.nombre }} en {{ s.tipoFlor.nombre }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Ingrediente activo</div>
                            <div>{{ s.producto.ingrediente_activo }}</div>
                        </div>
                        <div class="col-span-2">
                            <div class="text-gray-500">Condiciones</div>
                            <div class="line-clamp-3">{{ s.condiciones }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Dosis (ml/bomba)</div>
                            <div>{{ s.dosis_bomba_ml }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Dosis (ml/tambo)</div>
                            <div>{{ s.dosis_tambo_ml }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Stock</div>
                            <div>{{ s.producto.cantidad }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Actualización</div>
                            <div class="whitespace-nowrap">{{ s.producto.updated_at }}</div>
                        </div>
                    </div>
                </div>

                <!-- Paginación -->
                <div name="Pagination">
                    <Pagination class="mt-4" :links="solucionesByProduct.links" :prefix="''" />
                </div>
            </div>

            <!-- Vista en tabla (md y arriba) -->
            <div class="relative overflow-x-auto hidden md:block">
                <table class="w-full table-auto text-sm text-left text-gray-600">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Nombre del producto</th>
                            <th>Enfermedad en planta</th>
                            <th>Ingrediente activo</th>
                            <th>Condiciones de aplicación</th>
                            <th>Dosis (ml/bomba)</th>
                            <th>Dosis (ml/tambo)</th>
                            <th>Stock</th>
                            <th>Última actualización</th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-2">
                        <tr v-for="s in solucionesByProduct.data" :key="s.producto.id" class="border-b">
                            <td class="font-medium text-gray-900">{{ s.producto.nombre }}</td>
                            <td>{{ s.enfermedad.nombre }} en {{ s.tipoFlor.nombre }}</td>
                            <td>{{ s.producto.ingrediente_activo }}</td>
                            <td class="max-w-[28ch] truncate" title="{{ s.condiciones }}">{{ s.condiciones }}</td>
                            <td class="whitespace-nowrap">{{ s.dosis_bomba_ml }}</td>
                            <td class="whitespace-nowrap">{{ s.dosis_tambo_ml }}</td>
                            <td class="whitespace-nowrap">{{ s.producto.cantidad }}</td>
                            <td class="whitespace-nowrap">{{ s.producto.updated_at }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginación -->
                <div name="Pagination">
                    <Pagination class="mt-6" :links="solucionesByProduct.links" :prefix="''" />
                </div>
            </div>

        </div>
    </AppLayout>
</template>
