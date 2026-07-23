<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { useConnectivityStore } from '@/stores/connectivity';
import { offlineProductRepository } from '@/Offline/repositories/OfflineProductRepository';
import { notify } from '@/utils/notify';

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
const connectivity = useConnectivityStore();
const localProducts = ref([]);
const localLoading = ref(false);
const isOfflineCatalog = computed(() => !connectivity.isUsableOnline);

const loadLocalProducts = async (query = '') => {
    localLoading.value = true;
    try {
        localProducts.value = await offlineProductRepository.search(query);
    } catch {
        localProducts.value = [];
        notify('El catálogo local aún no está disponible. Conéctate una vez para sincronizarlo.', 'error');
    } finally {
        localLoading.value = false;
    }
};

watch(q, (value) => {
    if (isOfflineCatalog.value) {
        loadLocalProducts(value);
        return;
    }
    router.get(route('dashboard', { q: value }), {}, { preserveState: true });
});

watch(() => connectivity.mode, mode => {
    if (mode !== 'online') loadLocalProducts(q.value);
});

onMounted(() => {
    if (isOfflineCatalog.value) loadLocalProducts();
});

const formatCurrency = value => Number(value ?? 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const formatStockDate = value => value ? new Date(value).toLocaleString('es-MX') : 'Sin sincronización';

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

        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">

            <div v-if="isOfflineCatalog" class="mb-5 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                <p class="font-semibold">Catálogo local</p>
                <p>Los precios y existencias corresponden a la última sincronización. La existencia incluye movimientos pendientes de este dispositivo.</p>
            </div>

            <div v-if="isOfflineCatalog && localLoading" class="py-8 text-center text-sm text-gray-500">Consultando productos guardados…</div>

            <div v-if="isOfflineCatalog && !localLoading" class="grid grid-cols-1 gap-4 md:hidden">
                <article v-for="product in localProducts" :key="product.id" class="rounded-lg border bg-white p-4 shadow-sm">
                    <div class="font-semibold text-gray-900">{{ product.name }} <span v-if="product.size">- {{ product.size }}</span></div>
                    <div class="mt-1 text-xs text-gray-500">Código: {{ product.barcode || product.sku || 'N/D' }}</div>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div><div class="text-gray-500">Precio</div><strong>{{ formatCurrency(product.price) }}</strong></div>
                        <div><div class="text-gray-500">Existencia estimada</div><strong>{{ product.stock?.estimatedQuantity ?? 0 }}</strong></div>
                        <div><div class="text-gray-500">Existencia central</div><span>{{ product.stock?.serverQuantity ?? 0 }}</span></div>
                        <div><div class="text-gray-500">Movimientos locales</div><span>{{ product.stock?.localPendingDelta ?? 0 }}</span></div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500">Sincronizado: {{ formatStockDate(product.stock?.syncedAt) }}</div>
                </article>
                <p v-if="!localProducts.length" class="py-8 text-center text-gray-500">No se encontraron productos en el catálogo local.</p>
            </div>

            <div v-if="isOfflineCatalog && !localLoading" class="relative hidden overflow-x-auto md:block">
                <table class="w-full table-auto text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase"><tr class="[&>th]:px-4 [&>th]:py-3"><th>Producto</th><th>Código</th><th>Precio</th><th>Existencia central</th><th>Movimientos locales</th><th>Existencia estimada</th><th>Sincronización</th></tr></thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-3">
                        <tr v-for="product in localProducts" :key="product.id" class="border-b bg-white">
                            <td class="font-medium text-gray-900">{{ product.name }} <span v-if="product.size">- {{ product.size }}</span></td>
                            <td>{{ product.barcode || product.sku || 'N/D' }}</td>
                            <td>{{ formatCurrency(product.price) }}</td>
                            <td>{{ product.stock?.serverQuantity ?? 0 }}</td>
                            <td>{{ product.stock?.localPendingDelta ?? 0 }}</td>
                            <td class="font-semibold">{{ product.stock?.estimatedQuantity ?? 0 }}</td>
                            <td>{{ formatStockDate(product.stock?.syncedAt) }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="!localProducts.length" class="py-8 text-center text-gray-500">No se encontraron productos en el catálogo local.</p>
            </div>

            <!-- Vista en tarjetas (mobile) -->
            <div v-if="!isOfflineCatalog" class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4  w-full">
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
            <div v-if="!isOfflineCatalog" class="relative overflow-x-auto hidden md:block">
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
