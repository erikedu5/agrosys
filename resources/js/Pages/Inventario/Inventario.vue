<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, computed } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import UpdateProductPricesModal from '@/Components/UpdateProductPricesModal.vue';
import { mdiCashMultiple, mdiPencil, mdiPlusBox, mdiBackupRestore } from '@mdi/js';

const props = defineProps({
    productos: {
        type: Object,
        default: {}
    },
    auth: {
        type: Object,
        default: {}
    }
});

const q = ref('');
const page = usePage();
const showPriceModal = ref(false);
const selectedProduct = ref(null);
const icons = {
    price: mdiCashMultiple,
    edit: mdiPencil,
    add: mdiPlusBox,
    reset: mdiBackupRestore,
};

const puedeGestionarCostos = computed(() => {
    const tipoUsuario = page.props?.auth?.user?.tipo;
    const configMostrar = page.props?.empresaConfig?.mostrar_campos_precio ?? true;
    return tipoUsuario === 'adminEmpresa' || tipoUsuario === 'superAdmin' || configMostrar;
});

watch(q, (value) => {
    router.get(route('inventario.index', { q: value }), {}, { preserveState: true });
});

const agregarInventario = (id) => {
    useForm({}).get(route('inventario.show', id));
}

const resetInventario = (id) => {
    if (confirm('¿Seguro que deseas resetear el inventario a cero?')) {
        useForm({}).post(route('inventario.reset', id));
    }
}

const openPriceModal = (producto) => {
    selectedProduct.value = producto;
    showPriceModal.value = true;
};

const closePriceModal = () => {
    showPriceModal.value = false;
    selectedProduct.value = null;
};

const handlePriceUpdated = (payload) => {
    if (selectedProduct.value) {
        selectedProduct.value.precio_ieps = payload.precio_ieps;
        if (payload.precio_unitario !== undefined) {
            selectedProduct.value.precio_unitario = payload.precio_unitario;
        }
        if (payload.ieps !== undefined) {
            selectedProduct.value.ieps = payload.ieps;
        }
    }

    router.reload({
        preserveState: true,
        preserveScroll: true,
        only: ['productos'],
    });
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Inventario
            </h2>
            <br>
            <div class="flex justify-between">
                <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                    placeholder="Buscar Producto...">

            </div>
        </template>

        <hr class="my-6">
        <div class="flex justify-end max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-2">
            <Link :href="route('inventario.create')"
                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
            Nuevo producto +
            </Link>
        </div>
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-2">
            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="producto in productos.data" :key="producto.id"
                    class="rounded-lg boder p-4 bg-white shadow-lg">
                    <div class="text-sm text-gray-500">Nombre del producto</div>
                    <div class="font-semibold text-gray-900">{{ producto.nombre }}</div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-500">Ingrediente activo</div>
                            <div>{{ producto.ingrediente_activo }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Tamaño</div>
                            <div>{{ producto.tamano }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Marca</div>
                            <div>{{ producto.marca.nombre }}</div>
                        </div>

                        <div>
                            <div class="text-gray-500">Precio de venta</div>
                            <div>{{ producto.precio_ieps }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Cantidad en stock</div>
                            <div> {{ producto.cantidad }}</div>
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <div class="flex flex-wrap justify-end gap-2">
                            <button type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                @click="openPriceModal(producto)" title="Actualizar precios" aria-label="Actualizar precios">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path :d="icons.price"></path>
                                </svg>
                                <span class="sr-only">Actualizar precios</span>
                            </button>
                            <Link :href="route('inventario.edit', producto.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                title="Actualizar" aria-label="Actualizar">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path :d="icons.edit"></path>
                                </svg>
                                <span class="sr-only">Actualizar</span>
                            </Link>
                            <Link href="" @click.prevent="agregarInventario(producto.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                title="Agregar al inventario" aria-label="Agregar al inventario">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path :d="icons.add"></path>
                                </svg>
                                <span class="sr-only">Agregar al inventario</span>
                            </Link>
                            <Link v-if="props.auth.user.tipo == 'adminEmpresa' || props.auth.user.tipo == 'superAdmin'"
                                href="" @click="resetInventario(producto.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                title="Resetear inventario a cero" aria-label="Resetear inventario a cero">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path :d="icons.reset"></path>
                                </svg>
                                <span class="sr-only">Resetear inventario a cero</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Paginación -->
                <div name="Pagination">
                    <Pagination class="mt-6" :links="productos.links" />
                </div>

            </div>
            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id</th>
                            <th>Nombre del producto</th>
                            <th>Ingrediente activo</th>
                            <th>Tamaño</th>
                            <th>Marca</th>
                            <th>Cantidad en stock</th>
                            <th>Precio de venta</th>
                            <th>Ultima actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-2">
                        <tr v-for="producto in productos.data" :key="producto.id" class="border-b">
                            <td class="whitespace-nowrap"> {{ producto.id }}</td>
                            <td class="font-medium text-gray-900"> {{ producto.nombre }} </td>
                            <td class="whitespace-nowrap"> {{ producto.ingrediente_activo }} </td>
                            <td class="whitespace-nowrap"> {{ producto.tamano }} </td>
                            <td class="whitespace-nowrap"> {{ producto.marca.nombre }}</td>
                            <td class="whitespace-nowrap"> {{ producto.cantidad }}</td>
                            <td class="whitespace-nowrap"> {{ producto.precio_ieps }}</td>
                            <td class="whitespace-nowrap"> {{ producto.updated_at }} </td>
                            <td class="whitespace-nowrap">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                        @click="openPriceModal(producto)" title="Actualizar precios"
                                        aria-label="Actualizar precios">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path :d="icons.price"></path>
                                        </svg>
                                        <span class="sr-only">Actualizar precios</span>
                                    </button>
                                    <Link :href="route('inventario.edit', producto.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                        title="Actualizar" aria-label="Actualizar">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path :d="icons.edit"></path>
                                        </svg>
                                        <span class="sr-only">Actualizar</span>
                                    </Link>
                                    <Link href="" @click.prevent="agregarInventario(producto.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                        title="Agregar al inventario" aria-label="Agregar al inventario">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path :d="icons.add"></path>
                                        </svg>
                                        <span class="sr-only">Agregar al inventario</span>
                                    </Link>
                                    <Link href=""
                                        v-if="props.auth.user.tipo == 'adminEmpresa' || props.auth.user.tipo == 'superAdmin'"
                                        @click="resetInventario(producto.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                        title="Resetear inventario a cero" aria-label="Resetear inventario a cero">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path :d="icons.reset"></path>
                                        </svg>
                                        <span class="sr-only">Resetear inventario a cero</span>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div name="Pagination">
                    <Pagination class="mt-6" :links="productos.links" />
                </div>
            </div>


        </div>
        <UpdateProductPricesModal :show="showPriceModal" :producto="selectedProduct"
            :can-manage-costs="puedeGestionarCostos" @close="closePriceModal" @updated="handlePriceUpdated" />
    </AppLayout>
</template>
