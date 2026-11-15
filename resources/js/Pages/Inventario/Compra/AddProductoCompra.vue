<script setup>
import { ref, reactive, watch, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { formatMoneda } from '@/utils/formato';
import { usePersistedForm } from '@/stores/formStore';
import 'v-calendar/style.css';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
import moment from 'moment';
import VCalendar from 'v-calendar';
import InputError from '@/Components/InputError.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { notify } from '@/utils/notify';
import { usePage } from '@inertiajs/vue3';
import { purchaseDraft, purchaseKey } from '@/utils/purchaseDraft';
import { TIPOS_PRECIO } from '@/utils/precios';

const props = defineProps({
    compra: Object,
    productos: {
        type: Array,
        default: []
    },
    clasificaciones: {
        type: Array,
        default: []
    },
    marcas: {
        type: Array,
        default: []
    },
});

const page = usePage();
const mostrarCamposPrecio = computed(() => {
    const config = page.props.empresaConfig?.mostrar_campos_precio ?? true;
    const esAdminEmpresa = page.props.auth?.user?.tipo === 'adminEmpresa';
    return esAdminEmpresa ? true : config;
});

const statusOptions = [
    { value: 'adeudo', label: 'Adeudo' },
    { value: 'pagada', label: 'Pagada' },
    { value: 'retrasada', label: 'Retrasada' }
];

const productoOptions = ref(props.productos.map(p => ({ ...p, barcode: p.barcode ?? '' })));

const draftScope = `${page.props.auth?.user?.id}:${page.props.sucursalActiva?.id ?? page.props.auth?.user?.id_sucursal}`;
const draft = purchaseDraft({
    getItem: key => window.sessionStorage.getItem(key),
    setItem: (key, value) => window.sessionStorage.setItem(key, value),
    removeItem: key => window.sessionStorage.removeItem(key),
}, draftScope);
const initialData = {
    idempotency_key: props.compra === undefined ? purchaseKey() : null,
    id: props.compra !== undefined ? props.compra.id : null,
    proveedor: props.compra !== undefined ? props.compra.proveedor : '',
    fecha_compra: props.compra !== undefined ? new Date(props.compra.fecha_compra).toLocaleString('en-US', { timeZone: 'UTC' }) : new Date(),
    total_compra: props.compra !== undefined ? props.compra.total_compra : 0.0,
    status: props.compra !== undefined ? props.compra.status : '',
    productos: props.compra !== undefined ? props.compra.productos.map(p => ({ ...p, barcode: p.barcode ?? '' })) : [],
    abonos: props.compra !== undefined ? props.compra.abonos : [],
    total_credito: props.compra !== undefined ? props.compra.total_credito : 0.0,
    expected_debt: props.compra !== undefined ? props.compra.total_credito : null,
    fecha_credito: props.compra !== undefined ? props.compra.fecha_credito : null,
    producto: '',
    abono: '',
    cantidad_pedido: 1,
    abonoObj: {
        type: Object,
        default: () => ({})
    },
    precio_compra: 0,
    pagadaInicial: true
};
const { form, reset } = usePersistedForm(`compraForm:${draftScope}:${props.compra?.id ?? 'new'}`, {
    ...initialData,
    ...(props.compra === undefined ? draft.load() : {}),
});
form.defaults(initialData);
if (props.compra === undefined) {
    form.idempotency_key ||= purchaseKey();
    watch(() => form.data(), data => draft.save(data), { deep: true, immediate: true, flush: 'sync' });
}
const resetPurchase = () => {
    reset();
    if (props.compra === undefined) {
        form.idempotency_key = purchaseKey();
        form.defaults('idempotency_key', form.idempotency_key);
        draft.clear();
    }
};
const discardDraft = () => {
    if (window.confirm('¿Descartar este borrador? Si ya intentaste enviarlo, revisa primero el listado de compras.')) {
        resetPurchase();
    }
};

const statusSeleccionado = computed({
    get() {
        return statusOptions.find(o => o.value === form.status) || null;
    },
    set(value) {
        form.status = value ? value.value : '';
        changeStatus();
    }
});


const submit = () => {
    if (form.processing) return;
    if (form.status == 'adeudo' && form.abonos.length == 0) {
        notify('Debe agregar al menos un abono', 'error');
        return;
    }
    if (props.compra == undefined) {
        form.post(route('compra.store'), {
            onSuccess: resetPurchase,
        });
    } else {
        form.put(route('compra.update', props.compra.id), {
            onSuccess: resetPurchase,
        });
    }
}

const addProducto = () => {
    let existe = false;
    form.productos.forEach((item, index) => {
        if (!existe && form.producto.id === item.id) {
            item.cantidad += form.cantidad_pedido;
            item.precio_compra = form.precio_compra;
            item.subtotal = (item.cantidad * item.precio_compra);
            form.total_compra += item.subtotal;
            existe = true;
            form.cantidad_pedido = 1;
            form.producto = undefined;
            form.precio_compra = 0;
        }
    });
    if (existe) {
        return;
    }
    form.producto.cantidad = form.cantidad_pedido;
    form.producto.precio_compra = form.precio_compra;
    form.producto.subtotal = (form.cantidad_pedido * form.precio_compra);
    console.log(form.producto);
    console.log(form.total_compra);
    form.total_compra += form.producto.subtotal;
    form.total_credito += form.producto.subtotal;
    form.productos.push(form.producto);
    form.cantidad_pedido = 1;
    form.producto = undefined;
    form.precio_compra = 0;
}

const eliminarProducto = (id) => {
    form.productos.forEach((item, index) => {
        if (id === item.id) {
            form.total_compra -= item.subtotal;
            form.productos.splice(index, 1);
        }
    });
}

const showAddProductoModal = ref(false);

const openAddProductoModal = () => {
    showAddProductoModal.value = true;
};

const closeAddProductoModal = () => {
    showAddProductoModal.value = false;
};

const handleAddProducto = () => {
    addProducto();
    //closeAddProductoModal();
};

const showProductoModal = ref(false);
const productoForm = reactive({
    nombre: '',
    id_clasificacion: '',
    id_marca: '',
    precio_unitario: '',
    precio_publico: '',
    precio_preferencial_1: '',
    precio_preferencial_2: '',
    tamano: '',
    ingrediente_activo: '',
    barcode: '',
});
const productoErrors = ref({});

const productoClasificacionSeleccionada = ref(null);
watch(productoClasificacionSeleccionada, (v) => {
    productoForm.id_clasificacion = v ? v.id : '';
});

const productoMarcaSeleccionada = ref(null);
watch(productoMarcaSeleccionada, (v) => {
    productoForm.id_marca = v ? v.id : '';
});

const openProductoModal = () => {
    showProductoModal.value = true;
};

const closeProductoModal = () => {
    showProductoModal.value = false;
    productoForm.nombre = '';
    productoForm.id_clasificacion = '';
    productoForm.id_marca = '';
    productoForm.precio_unitario = '';
    productoForm.precio_publico = '';
    productoForm.precio_preferencial_1 = '';
    productoForm.precio_preferencial_2 = '';
    productoForm.tamano = '';
    productoForm.ingrediente_activo = '';
    productoForm.barcode = '';
    productoErrors.value = {};
};

const guardarProducto = () => {
    axios.post(route('inventario.store'), productoForm, { headers: { Accept: 'application/json' } })
        .then(response => {
            const newProducto = { ...response.data, barcode: response.data.barcode ?? '' };
            productoOptions.value.push(newProducto);
            form.producto = newProducto;
            closeProductoModal();
        })
        .catch(error => {
            if (error.response && error.response.status === 422) {
                productoErrors.value = error.response.data.errors;
                Object.values(productoErrors.value).forEach((message) => {
                    if (message) {
                        notify(message, 'error');
                    }
                });
            }
        });
};

const agregarAbono = () => {
    form.abonoObj.cantidad_abonada = parseFloat(form.abono).toFixed(2);
    form.abonoObj.created_at = moment(new Date()).format('YYYY-MM-DD hh:mm:ss');
    form.abonos.push(form.abonoObj);
    form.total_credito -= form.abono;
    if (form.total_credito == 0) {
        form.status = 'pagada';
        form.pagadaInicial = false;
    }
    form.abono = 0;
    form.abonoObj = {};
}

const changeStatus = (event) => {
    if (form.status == 'pagada') {
        form.abono = form.total_compra;
        form.pagadaInicial = false;
        agregarAbono();
    } else if (form.status == 'adeudo') {
        // Si se cambia a adeudo, restaurar el total de crédito y borrar abonos
        form.total_credito = form.total_compra;
        form.abonos = [];
        form.abono = 0;
        form.abonoObj = {};
    } else if (form.status == 'atrasada') { 
        return;
    }
}
</script>

<template>
    <AppLayout title="Compra">
        <template #header>
            <PageHeader :title="props.compra ? `Compra #${props.compra.id}` : 'Nueva compra'" subtitle="Productos, total y abonos" :back="route('compra.index')" />
        </template>

        <div class="ui-page">
            <form class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_400px] lg:items-start" @submit.prevent="submit">
                <div class="flex min-w-0 flex-col gap-4">
                    <section class="ui-card p-4 sm:p-5">
                        <div class="ui-field">
                            <label class="ui-label">Nombre del proveedor</label>
                            <input type="text" :disabled="props.compra !== undefined" class="ui-input" v-model="form.proveedor">
                            <InputError :message="form.errors.proveedor" />
                        </div>
                    </section>

                    <section class="ui-card overflow-hidden">
                        <div class="flex items-center justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <h2 class="ui-section-title">Productos</h2>
                                <p class="ui-muted">{{ form.productos.length === 1 ? '1 producto' : `${form.productos.length} productos` }}</p>
                            </div>
                            <button type="button" @click="openAddProductoModal" :disabled="form.status !== ''" class="ui-btn ui-btn-primary"
                                v-tooltip="form.status !== '' ? 'Esta compra ya se cambió de status, por lo que no se le pueden agregar más productos' : ''">
                                Agregar producto
                            </button>
                        </div>
                        <div v-for="producto in form.productos" :key="producto.id" class="ui-row">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ producto.nombre }}</p>
                                <p class="text-[13px] text-gray-600 dark:text-gray-400">{{ producto.cantidad }} × {{ formatMoneda(producto.precio_compra) }}</p>
                            </div>
                            <p class="whitespace-nowrap text-[17px] font-bold">{{ formatMoneda(producto.subtotal) }}</p>
                            <button type="button" :disabled="props.compra !== undefined" @click="eliminarProducto(producto.id)" :aria-label="`Eliminar ${producto.nombre}`"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-red-700 disabled:opacity-40 dark:hover:bg-gray-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                        <p v-if="!form.productos.length" class="ui-row ui-muted">Aún no hay productos en esta compra.</p>
                    </section>

                    <section class="ui-card overflow-hidden">
                        <div class="flex flex-col gap-4 p-4">
                            <h2 class="ui-section-title">Abonos</h2>
                            <div v-if="props.compra !== undefined || form.status == 'adeudo'" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div class="ui-field flex-1">
                                    <label class="ui-label">Abonar a crédito</label>
                                    <input type="number" step="0.01" class="ui-input text-right text-xl font-bold" :disabled="form.status == 'pagada'" v-model="form.abono">
                                </div>
                                <button type="button" @click="agregarAbono()" :disabled="form.status == 'pagada'" class="ui-btn ui-btn-success h-12">Abonar</button>
                            </div>
                        </div>
                        <div v-for="abono in form.abonos" :key="abono.id" class="ui-row">
                            <span class="ui-muted min-w-0 flex-1">{{ abono.created_at }}</span>
                            <span class="font-bold">{{ formatMoneda(abono.cantidad_abonada) }}</span>
                        </div>
                        <p v-if="!form.abonos.length" class="ui-row ui-muted">Sin abonos registrados.</p>
                    </section>
                </div>

                <aside class="ui-card flex flex-col gap-4 p-4 sm:p-5 lg:sticky lg:top-24">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-900">
                        <p class="ui-muted">Total de la compra</p>
                        <p class="text-4xl font-extrabold">{{ formatMoneda(form.total_compra) }}</p>
                    </div>
                    <InputError :message="form.errors.total_compra" />

                    <div class="flex items-center justify-between rounded-xl bg-blue-50 px-4 py-3.5 text-blue-900 dark:bg-blue-950 dark:text-blue-200">
                        <span class="font-semibold">Saldo a crédito</span>
                        <span class="text-2xl font-extrabold">{{ formatMoneda(form.total_credito) }}</span>
                    </div>
                    <InputError :message="form.errors.total_credito" />

                    <div class="ui-field">
                        <label class="ui-label">Estatus de la compra</label>
                        <vue-single-select v-model="statusSeleccionado" :options="statusOptions" option-key="value"
                            class="w-full" :disabled="form.status == 'pagada'" placeholder="Seleccione" />
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="ui-field">
                        <span class="ui-label">Fecha de la compra</span>
                        <VDatePicker
                            :max-date="props.compra !== undefined ? form.fecha_compra : null"
                            :min-date="props.compra !== undefined ? form.fecha_compra : null"
                            v-model="form.fecha_compra" expanded />
                        <InputError :message="form.errors.fecha_compra" />
                    </div>

                    <button :disabled="form.processing || (props.compra !== undefined && form.status != 'adeudo' && form.pagadaInicial)"
                        class="ui-btn ui-btn-primary ui-btn-lg h-16 w-full text-lg">
                        {{ form.processing ? 'Guardando…' : 'Guardar compra' }}
                    </button>
                    <button v-if="props.compra === undefined" type="button" :disabled="form.processing" class="ui-btn ui-btn-danger w-full" @click="discardDraft">
                        Descartar borrador
                    </button>
                </aside>
            </form>
        </div>

        <DialogModal :show="showAddProductoModal" @close="closeAddProductoModal">
            <template #title>
                Agregar producto
            </template>

            <template #content>
                <div class="mt-2 flex flex-col gap-4">
                    <div class="ui-field">
                        <div class="flex items-center justify-between gap-3">
                            <label class="ui-label">Nombre del producto</label>
                            <button type="button" @click="openProductoModal" class="ui-btn h-9 px-3">Crear producto</button>
                        </div>
                        <vue-single-select placeholder="Seleccione un producto" v-model="form.producto" option-key="barcode"
                            option-label="nombre" :options="productoOptions">
                        </vue-single-select>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="ui-field">
                            <label class="ui-label">Cantidad de pedido</label>
                            <input type="number" min="1" step="0.01" class="ui-input" v-model="form.cantidad_pedido">
                        </div>
                        <div class="ui-field">
                            <label class="ui-label">Precio de compra</label>
                            <input type="number" step="0.01" class="ui-input" v-model="form.precio_compra">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button type="button" @click="closeAddProductoModal" class="ui-btn ui-btn-lg">Cerrar</button>
                        <button type="button" @click="handleAddProducto" class="ui-btn ui-btn-primary ui-btn-lg">Agregar</button>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="ui-card w-full overflow-hidden text-left">
                    <div v-for="producto in form.productos" :key="producto.id" class="ui-row first:border-t-0">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ producto.nombre }}</p>
                            <p class="text-[13px] text-gray-600 dark:text-gray-400">{{ producto.cantidad }} × {{ formatMoneda(producto.precio_compra) }}</p>
                        </div>
                        <p class="whitespace-nowrap font-bold">{{ formatMoneda(producto.subtotal) }}</p>
                        <button type="button" :disabled="props.compra !== undefined" @click="eliminarProducto(producto.id)" :aria-label="`Eliminar ${producto.nombre}`"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-red-700 disabled:opacity-40 dark:hover:bg-gray-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                    <p v-if="!form.productos.length" class="ui-row ui-muted first:border-t-0">Aún no hay productos en esta compra.</p>
                </div>
            </template>
        </DialogModal>

        <DialogModal :show="showProductoModal" @close="closeProductoModal">
            <template #title>
                Crear producto
            </template>

            <template #content>
                <div class="mt-2 flex flex-col gap-4">
                    <div class="ui-field">
                        <label class="ui-label">Nombre</label>
                        <input class="ui-input" v-model="productoForm.nombre">
                        <InputError :message="productoErrors.nombre" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="ui-field">
                            <label class="ui-label">Clasificación</label>
                            <vue-single-select v-model="productoClasificacionSeleccionada" :options="clasificaciones" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            <InputError :message="productoErrors.id_clasificacion" />
                        </div>
                        <div class="ui-field">
                            <label class="ui-label">Marca</label>
                            <vue-single-select v-model="productoMarcaSeleccionada" :options="marcas" option-key="id" option-label="nombre" placeholder="Seleccione" class="w-full" />
                            <InputError :message="productoErrors.id_marca" />
                        </div>
                    </div>

                    <div v-if="mostrarCamposPrecio" class="ui-field">
                        <label class="ui-label">Precio de compra</label>
                        <input type="number" step="0.01" min="0" class="ui-input" v-model="productoForm.precio_unitario">
                        <InputError :message="productoErrors.precio_unitario" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div v-for="tipo in TIPOS_PRECIO" :key="tipo.campo" class="ui-field">
                            <label class="ui-label">Precio {{ tipo.label }}</label>
                            <input type="number" step="0.01" min="0.01" class="ui-input" v-model="productoForm[tipo.campo]">
                            <InputError :message="productoErrors[tipo.campo]" />
                        </div>
                    </div>
                    <p class="ui-help -mt-2">Los precios preferenciales sin capturar toman el precio público.</p>

                    <div class="ui-field">
                        <label class="ui-label">Tamaño</label>
                        <input class="ui-input" v-model="productoForm.tamano">
                        <InputError :message="productoErrors.tamano" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="ui-field">
                            <label class="ui-label">Ingrediente activo</label>
                            <input class="ui-input" v-model="productoForm.ingrediente_activo">
                            <InputError :message="productoErrors.ingrediente_activo" />
                        </div>
                        <div class="ui-field">
                            <label class="ui-label">Código de barras</label>
                            <input class="ui-input" v-model="productoForm.barcode">
                            <InputError :message="productoErrors.barcode" />
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="closeProductoModal" class="ui-btn">Cancelar</button>
                    <button type="button" @click="guardarProducto" class="ui-btn ui-btn-primary">Guardar</button>
                </div>
            </template>
        </DialogModal>
    </AppLayout>
</template>
