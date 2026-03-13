<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, computed } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import UpdateProductPricesModal from '@/Components/UpdateProductPricesModal.vue';
import CardexModal from '@/Components/CardexModal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { mdiCashMultiple, mdiPencil, mdiPlusBox, mdiBackupRestore, mdiClipboardListOutline, mdiTrashCanOutline, mdiTrendingUp } from '@mdi/js';
import { notify } from '@/utils/notify';

const props = defineProps({
    productos: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    auth: {
        type: Object,
        default: {}
    },
    ventasBloqueadas: {
        type: Boolean,
        default: false
    },
    motivoBloqueo: {
        type: String,
        default: null
    }
});

const q = ref('');
const page = usePage();
const showPriceModal = ref(false);
const selectedProduct = ref(null);
const showCardexModal = ref(false);
const cardexProducto = ref(null);
const showDeleteModal = ref(false);
const productoEliminar = ref(null);
const deletingProduct = ref(false);
const icons = {
    price: mdiCashMultiple,
    edit: mdiPencil,
    add: mdiPlusBox,
    reset: mdiBackupRestore,
    cardex: mdiClipboardListOutline,
    delete: mdiTrashCanOutline,
    trending: mdiTrendingUp,
};

// --- Reporte de aumentos de inventario ---
const today = new Date().toISOString().split('T')[0];
const showReporteAumentosModal = ref(false);
const reporteFechaInicio = ref(today);
const reporteFechaFin = ref(today);

const openReporteAumentos = () => {
    reporteFechaInicio.value = today;
    reporteFechaFin.value = today;
    showReporteAumentosModal.value = true;
};

const cerrarReporteAumentos = () => {
    showReporteAumentosModal.value = false;
};

const descargarReporteAumentos = () => {
    const url = route('reporte.aumentosInventario', {
        fechaInicio: reporteFechaInicio.value,
        fechaFin:    reporteFechaFin.value,
    });
    window.open(url, '_blank');
    cerrarReporteAumentos();
};

const puedeGestionarCostos = computed(() => {
    const tipoUsuario = page.props?.auth?.user?.tipo;
    const configMostrar = page.props?.empresaConfig?.mostrar_campos_precio ?? true;
    return tipoUsuario === 'adminEmpresa' || tipoUsuario === 'superAdmin' || configMostrar;
});

const errors = computed(() => page.props?.errors ?? {});
const mensajeBloqueo = computed(() => props.motivoBloqueo || errors.value.bloqueo || 'Esta sección está bloqueada, Contacte a su administrador.');
const bloqueoActivo = computed(() => Boolean(props.ventasBloqueadas) || Boolean(errors.value.bloqueo));
const puedeEliminar = computed(() => ['adminEmpresa', 'superAdmin'].includes(page.props?.auth?.user?.tipo));

watch(bloqueoActivo, (value) => {
    if (value) {
        notify(mensajeBloqueo.value, 'error');
    }
}, { immediate: true });

watch(q, (value) => {
    if (bloqueoActivo.value) {
        return;
    }
    router.get(route('inventario.index', { q: value }), {}, { preserveState: true });
});

const agregarInventario = (id) => {
    if (bloqueoActivo.value) {
        return;
    }
    useForm({}).get(route('inventario.show', id));
}

const resetInventario = (id) => {
    if (bloqueoActivo.value) {
        return;
    }
    if (confirm('¿Seguro que deseas resetear el inventario a cero?')) {
        useForm({}).post(route('inventario.reset', id));
    }
}

const openPriceModal = (producto) => {
    if (bloqueoActivo.value) {
        return;
    }
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

const openCardex = (producto) => {
    if (bloqueoActivo.value) {
        return;
    }
    cardexProducto.value = producto;
    showCardexModal.value = true;
};

const closeCardexModal = () => {
    showCardexModal.value = false;
    cardexProducto.value = null;
};

const eliminarProducto = (producto) => {
    if (bloqueoActivo.value || !producto) {
        return;
    }
    productoEliminar.value = producto;
    showDeleteModal.value = true;
};

const cerrarEliminar = () => {
    showDeleteModal.value = false;
    productoEliminar.value = null;
};

const confirmarEliminar = () => {
    if (!productoEliminar.value || deletingProduct.value) {
        return;
    }
    deletingProduct.value = true;
    router.delete(route('inventario.destroy', productoEliminar.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            notify('Producto eliminado correctamente.', 'success');
            cerrarEliminar();
        },
        onError: (errors) => {
            Object.values(errors || {}).forEach((message) => {
                if (Array.isArray(message)) {
                    message.forEach((item) => {
                        if (typeof item === 'string' && item.length) {
                            notify(item, 'error');
                        }
                    });
                    return;
                }
                if (typeof message === 'string' && message.length) {
                    notify(message, 'error');
                }
            });
            cerrarEliminar();
        },
        onFinish: () => {
            deletingProduct.value = false;
        },
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
            <div v-if="!bloqueoActivo" class="flex justify-between">
                <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                    placeholder="Buscar Producto...">

            </div>
        </template>

        <hr class="my-6">
        <div v-if="bloqueoActivo" class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
            <div class="rounded-md border border-red-200 bg-red-50 p-4 text-red-800">
                <p class="font-semibold">Inventario bloqueado</p>
                <p class="mt-1 text-sm">{{ mensajeBloqueo }}</p>
            </div>
        </div>
        <div v-if="!bloqueoActivo" class="flex justify-end gap-2 max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-2">
            <button
                type="button"
                @click="openReporteAumentos"
                class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded
                       hover:bg-green-700 focus:z-10 focus:ring-2 focus:ring-green-500
                       dark:bg-green-700 dark:hover:bg-green-600 dark:focus:ring-green-500"
                title="Reporte de aumentos de inventario"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path :d="icons.trending" />
                </svg>
                Reporte de Aumentos
            </button>
            <Link :href="route('inventario.create')"
                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
            Nuevo producto +
            </Link>
        </div>
        <div v-if="!bloqueoActivo" class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-2">
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
                            <button type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                @click="openCardex(producto)" title="Ver cardex" aria-label="Ver cardex">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path :d="icons.cardex"></path>
                                </svg>
                                <span class="sr-only">Ver cardex</span>
                            </button>
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
                            <button v-if="puedeEliminar" type="button"
                                class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded shadow-sm hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-red-400 dark:text-red-300 dark:hover:text-red-200 dark:hover:bg-gray-600 dark:focus:ring-red-400 flex flex-center"
                                @click="eliminarProducto(producto)" title="Eliminar producto" aria-label="Eliminar producto">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path :d="icons.delete"></path>
                                </svg>
                                <span class="sr-only">Eliminar producto</span>
                            </button>
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
                                    <button type="button"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white flex flex-center"
                                        @click="openCardex(producto)" title="Ver cardex" aria-label="Ver cardex">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path :d="icons.cardex"></path>
                                        </svg>
                                        <span class="sr-only">Ver cardex</span>
                                    </button>
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
                                    <button v-if="puedeEliminar" type="button"
                                        class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded shadow-sm hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-red-400 dark:text-red-300 dark:hover:text-red-200 dark:hover:bg-gray-600 dark:focus:ring-red-400 flex flex-center"
                                        @click="eliminarProducto(producto)" title="Eliminar producto" aria-label="Eliminar producto">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path :d="icons.delete"></path>
                                        </svg>
                                        <span class="sr-only">Eliminar producto</span>
                                    </button>
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
        <CardexModal :show="showCardexModal" :producto="cardexProducto" @close="closeCardexModal" />

        <!-- Modal: Reporte de Aumentos de Inventario -->
        <Transition name="fade">
            <div v-if="showReporteAumentosModal"
                class="fixed inset-0 z-50 flex items-center justify-center"
                role="dialog" aria-modal="true" aria-labelledby="modal-reporte-title"
            >
                <!-- backdrop -->
                <div class="absolute inset-0 bg-black/40" @click="cerrarReporteAumentos"></div>

                <!-- panel -->
                <div class="relative z-10 bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6">
                    <h3 id="modal-reporte-title"
                        class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="h-5 w-5 text-green-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path :d="icons.trending" />
                        </svg>
                        Reporte de Aumentos de Inventario
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label for="reporte-fecha-inicio"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Fecha inicio
                            </label>
                            <input
                                id="reporte-fecha-inicio"
                                type="date"
                                v-model="reporteFechaInicio"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                       px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                            />
                        </div>
                        <div>
                            <label for="reporte-fecha-fin"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Fecha fin
                            </label>
                            <input
                                id="reporte-fecha-fin"
                                type="date"
                                v-model="reporteFechaFin"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                       px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="cerrarReporteAumentos"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700
                                   border border-gray-300 dark:border-gray-500 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600
                                   focus:outline-none focus:ring-2 focus:ring-gray-400"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="descargarReporteAumentos"
                            :disabled="!reporteFechaInicio || !reporteFechaFin"
                            class="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium text-white
                                   bg-green-600 border border-transparent rounded-md hover:bg-green-700
                                   focus:outline-none focus:ring-2 focus:ring-green-500
                                   disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path :d="icons.trending" />
                            </svg>
                            Descargar PDF
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
        <ConfirmationModal :show="showDeleteModal" @close="cerrarEliminar">
            <template #title>
                Eliminar producto
            </template>
            <template #content>
                <p>
                    ¿Deseas eliminar <span class="font-semibold">{{ productoEliminar?.nombre }}</span>?
                </p>
                <p class="mt-2 text-xs text-gray-500">
                    Solo se permite si el stock está en cero en todas las sucursales de la empresa.
                </p>
            </template>
            <template #footer>
                <SecondaryButton @click="cerrarEliminar">
                    Cancelar
                </SecondaryButton>
                <button
                    class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
                    type="button"
                    :disabled="deletingProduct"
                    @click="confirmarEliminar"
                >
                    {{ deletingProduct ? 'Eliminando...' : 'Eliminar' }}
                </button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
