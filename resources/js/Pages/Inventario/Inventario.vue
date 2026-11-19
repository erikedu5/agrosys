<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchField from '@/Components/SearchField.vue';
import RecordCard from '@/Components/RecordCard.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { formatFecha, formatMoneda } from '@/utils/formato';
import { ref, watch, computed } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import PaginationBar from '@/Components/PaginationBar.vue';
import UpdateProductPricesModal from '@/Components/UpdateProductPricesModal.vue';
import CardexModal from '@/Components/CardexModal.vue';
import DosificarModal from '@/Components/DosificarModal.vue';
import EditProductoModal from '@/Components/EditProductoModal.vue';
import AddInventarioModal from '@/Components/AddInventarioModal.vue';
import SolucionesModal from '@/Components/SolucionesModal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { mdiBeakerOutline, mdiCashMultiple, mdiPencil, mdiPlusBox, mdiBackupRestore, mdiClipboardListOutline, mdiSprayBottle, mdiTrashCanOutline, mdiTrendingUp } from '@mdi/js';
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
    marcas: {
        type: Array,
        default: () => []
    },
    clasificaciones: {
        type: Array,
        default: () => []
    },
    filtros: {
        type: Object,
        default: () => ({ q: '', marca: null, existencia: null })
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

const q = ref(props.filtros.q ?? '');
const marcaActiva = ref(props.filtros.marca ?? null);
const existenciaActiva = ref(props.filtros.existencia ?? null);
const existencias = [
    { value: null, label: 'Todas' },
    { value: 'normal', label: 'Normal', punto: 'bg-green-600' },
    { value: 'baja', label: 'Baja', punto: 'bg-amber-400' },
    { value: 'agotado', label: 'Agotado', punto: 'bg-red-500' },
];
const chipClass = (activo) => activo
    ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200';
const hayFiltros = computed(() => Boolean(q.value) || marcaActiva.value !== null || existenciaActiva.value !== null);
const page = usePage();
const showPriceModal = ref(false);
const selectedProduct = ref(null);
const showCardexModal = ref(false);
const cardexProducto = ref(null);
const showDosificarModal = ref(false);
const dosificarProducto = ref(null);
const showEditModal = ref(false);
const editProducto = ref(null);
const showAddInventarioModal = ref(false);
const addInventarioProducto = ref(null);
const showSolucionesModal = ref(false);
const solucionesProducto = ref(null);
const showDeleteModal = ref(false);
const productoEliminar = ref(null);
const deletingProduct = ref(false);
const icons = {
    price: mdiCashMultiple,
    edit: mdiPencil,
    add: mdiPlusBox,
    reset: mdiBackupRestore,
    cardex: mdiClipboardListOutline,
    dose: mdiBeakerOutline,
    soluciones: mdiSprayBottle,
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

// Cualquier cambio de búsqueda o filtro vuelve a la primera página.
watch([q, marcaActiva, existenciaActiva], ([busqueda, marca, existencia]) => {
    if (bloqueoActivo.value) {
        return;
    }
    const params = {};
    if (busqueda) params.q = busqueda;
    if (marca !== null) params.marca = marca;
    if (existencia !== null) params.existencia = existencia;
    router.get(route('inventario.index', params), {}, { preserveState: true, preserveScroll: true, replace: true });
});

const agregarInventario = (producto) => {
    if (bloqueoActivo.value) {
        return;
    }
    addInventarioProducto.value = producto;
    showAddInventarioModal.value = true;
};

const closeAddInventario = () => {
    showAddInventarioModal.value = false;
    addInventarioProducto.value = null;
};

const openEditModal = (producto) => {
    if (bloqueoActivo.value) {
        return;
    }
    editProducto.value = producto;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editProducto.value = null;
};

const openSoluciones = (producto) => {
    if (bloqueoActivo.value) {
        return;
    }
    solucionesProducto.value = producto;
    showSolucionesModal.value = true;
};

const closeSoluciones = () => {
    showSolucionesModal.value = false;
    solucionesProducto.value = null;
};

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
        Object.assign(selectedProduct.value, payload);
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

const openDosificar = (producto) => {
    if (bloqueoActivo.value) {
        return;
    }
    dosificarProducto.value = producto;
    showDosificarModal.value = true;
};

const closeDosificar = () => {
    showDosificarModal.value = false;
    dosificarProducto.value = null;
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
    <AppLayout title="Inventario">
        <template #header>
            <PageHeader title="Inventario" subtitle="Productos, precios y existencias">
                <template v-if="!bloqueoActivo">
                    <button type="button" class="ui-btn flex-1 sm:flex-none" @click="openReporteAumentos" title="Reporte de aumentos de inventario">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="icons.trending" /></svg>
                        Reporte de aumentos
                    </button>
                    <Link :href="route('inventario.create')" class="ui-btn ui-btn-primary flex-1 sm:flex-none">Nuevo producto</Link>
                </template>
            </PageHeader>
        </template>

        <div class="ui-page">
            <div v-if="bloqueoActivo" class="ui-alert ui-alert-red" role="alert">
                <div>
                    <p class="font-semibold">Inventario bloqueado</p>
                    <p class="mt-1">{{ mensajeBloqueo }}</p>
                </div>
            </div>

            <template v-else>
                <SearchField v-model="q" placeholder="Buscar producto" />

                <div class="-mx-1 flex gap-2 overflow-x-auto px-1 py-0.5 [scrollbar-width:none]" role="tablist" aria-label="Existencia">
                    <button v-for="opcion in existencias" :key="opcion.label" type="button" role="tab"
                        :aria-selected="existenciaActiva === opcion.value" @click="existenciaActiva = opcion.value"
                        class="inline-flex h-11 shrink-0 items-center gap-2 rounded-full border px-4 text-[15px] font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        :class="chipClass(existenciaActiva === opcion.value)">
                        <span v-if="opcion.punto" class="h-2.5 w-2.5 rounded-full" :class="opcion.punto" aria-hidden="true"></span>
                        {{ opcion.label }}
                    </button>
                </div>

                <div v-if="marcas.length" class="-mx-1 flex gap-2 overflow-x-auto px-1 py-0.5 [scrollbar-width:none]" role="tablist" aria-label="Marca">
                    <button type="button" role="tab" :aria-selected="marcaActiva === null" @click="marcaActiva = null"
                        class="h-11 shrink-0 rounded-full border px-4 text-[15px] font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        :class="chipClass(marcaActiva === null)">
                        Todas las marcas
                    </button>
                    <button v-for="marca in marcas" :key="marca.id" type="button" role="tab"
                        :aria-selected="marcaActiva === marca.id" @click="marcaActiva = marca.id"
                        class="h-11 shrink-0 rounded-full border px-4 text-[15px] font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        :class="chipClass(marcaActiva === marca.id)">
                        {{ marca.nombre }}
                    </button>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ productos.total === 1 ? '1 producto' : `${productos.total ?? productos.data.length} productos` }}
                </p>

                <div class="ui-grid">
                    <RecordCard v-for="producto in productos.data" :key="producto.id"
                        class="border-t-4"
                        :class="Number(producto.cantidad) <= 0 ? 'border-t-red-500' : Number(producto.cantidad) <= 5 ? 'border-t-amber-400' : 'border-t-green-600'"
                        :eyebrow="producto.marca?.nombre ?? `#${producto.id}`" :title="producto.nombre"
                        :subtitle="[producto.tamano, producto.ingrediente_activo].filter(Boolean).join(' · ')"
                        :fields="[
                            { label: 'Distribuidor 1', value: formatMoneda(producto.precio_preferencial_1) },
                            { label: 'Distribuidor 2', value: formatMoneda(producto.precio_preferencial_2) },
                            { label: 'Distribuidor 3', value: formatMoneda(producto.precio_preferencial_3) },
                            { label: 'Id', value: producto.id },
                            { label: 'Última actualización', value: formatFecha(producto.updated_at) },
                        ]">
                        <template #badge>
                            <span class="ui-badge"
                                :class="Number(producto.cantidad) <= 0 ? 'ui-badge-red' : Number(producto.cantidad) <= 5 ? 'ui-badge-amber' : 'ui-badge-green'">
                                {{ Number(producto.cantidad) <= 0 ? 'Agotado' : `${producto.cantidad} en exist.` }}
                            </span>
                        </template>

                        <p class="flex items-end justify-between gap-2">
                            <span class="text-xl font-extrabold">{{ formatMoneda(producto.precio_publico) }}</span>
                            <span class="text-xs text-gray-600 dark:text-gray-400">Precio público</span>
                        </p>

                        <template #actions>
                            <button type="button" class="ui-btn ui-btn-icon" @click="openPriceModal(producto)" title="Actualizar precios" aria-label="Actualizar precios">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="icons.price" /></svg>
                            </button>
                            <button type="button" class="ui-btn ui-btn-icon" @click="openEditModal(producto)" title="Editar producto" aria-label="Editar producto">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="icons.edit" /></svg>
                            </button>
                            <button type="button" class="ui-btn ui-btn-icon" @click="openSoluciones(producto)" title="Soluciones" aria-label="Soluciones">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="icons.soluciones" /></svg>
                            </button>
                            <button type="button" class="ui-btn ui-btn-icon" @click="openCardex(producto)" title="Ver cardex" aria-label="Ver cardex">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="icons.cardex" /></svg>
                            </button>
                            <button type="button" class="ui-btn ui-btn-icon" @click="agregarInventario(producto)" title="Agregar al inventario" aria-label="Agregar al inventario">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="icons.add" /></svg>
                            </button>
                            <button type="button" class="ui-btn ui-btn-icon disabled:cursor-not-allowed disabled:opacity-40" @click="openDosificar(producto)"
                                :disabled="Number(producto.cantidad) < 1" :title="Number(producto.cantidad) < 1 ? 'Sin existencias para dosificar' : 'Dosificar'" aria-label="Dosificar">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="icons.dose" /></svg>
                            </button>
                            <button v-if="props.auth.user.tipo == 'adminEmpresa' || props.auth.user.tipo == 'superAdmin'" type="button"
                                class="ui-btn ui-btn-icon" @click="resetInventario(producto.id)" title="Resetear inventario a cero" aria-label="Resetear inventario a cero">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="icons.reset" /></svg>
                            </button>
                            <span class="flex-1"></span>
                            <button v-if="puedeEliminar" type="button" class="ui-btn ui-btn-icon ui-btn-danger" @click="eliminarProducto(producto)" title="Eliminar producto" aria-label="Eliminar producto">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="icons.delete" /></svg>
                            </button>
                        </template>
                    </RecordCard>
                </div>

                <EmptyState v-if="!productos.data?.length" :title="hayFiltros ? 'Ningún producto coincide con los filtros' : 'Aún no hay productos'"
                    :text="hayFiltros ? 'Cambia la búsqueda, la marca o la existencia.' : 'Crea un producto nuevo para verlo aquí.'" />
                <PaginationBar :paginator="productos" />
            </template>
        </div>

        <UpdateProductPricesModal :show="showPriceModal" :producto="selectedProduct"
            :can-manage-costs="puedeGestionarCostos" @close="closePriceModal" @updated="handlePriceUpdated" />
        <CardexModal :show="showCardexModal" :producto="cardexProducto" @close="closeCardexModal" />
        <DosificarModal :show="showDosificarModal" :producto="dosificarProducto" @close="closeDosificar" />
        <EditProductoModal :show="showEditModal" :producto="editProducto" :clasificaciones="clasificaciones" :marcas="marcas"
            :can-manage-costs="puedeGestionarCostos" @close="closeEditModal" />
        <AddInventarioModal :show="showAddInventarioModal" :producto="addInventarioProducto" @close="closeAddInventario" />
        <SolucionesModal :show="showSolucionesModal" :producto="solucionesProducto" @close="closeSoluciones" />

        <!-- Reporte de aumentos de inventario -->
        <div v-if="showReporteAumentosModal" class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/55 sm:items-center sm:p-6" @click.self="cerrarReporteAumentos">
            <div role="dialog" aria-modal="true" aria-labelledby="modal-reporte-title" class="flex w-full flex-col gap-4 rounded-t-2xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:max-w-sm sm:rounded-2xl">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="modal-reporte-title" class="text-xl font-bold text-gray-900 dark:text-gray-100">Reporte de aumentos</h2>
                    <button type="button" @click="cerrarReporteAumentos" aria-label="Cerrar" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="ui-field">
                    <label for="reporte-fecha-inicio" class="ui-label">Fecha inicio</label>
                    <input id="reporte-fecha-inicio" type="date" v-model="reporteFechaInicio" class="ui-input" />
                </div>
                <div class="ui-field">
                    <label for="reporte-fecha-fin" class="ui-label">Fecha fin</label>
                    <input id="reporte-fecha-fin" type="date" v-model="reporteFechaFin" class="ui-input" />
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <button type="button" class="ui-btn ui-btn-lg" @click="cerrarReporteAumentos">Cancelar</button>
                    <button type="button" class="ui-btn ui-btn-success ui-btn-lg" @click="descargarReporteAumentos" :disabled="!reporteFechaInicio || !reporteFechaFin">Descargar PDF</button>
                </div>
            </div>
        </div>

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
                <div class="flex justify-end gap-2">
                    <button type="button" class="ui-btn" @click="cerrarEliminar">Cancelar</button>
                    <button type="button" class="ui-btn border-transparent bg-red-600 text-white hover:bg-red-700 dark:border-transparent dark:bg-red-600 dark:text-white dark:hover:bg-red-700" :disabled="deletingProduct" @click="confirmarEliminar">
                        {{ deletingProduct ? 'Eliminando...' : 'Eliminar' }}
                    </button>
                </div>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
