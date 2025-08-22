<script setup>
import { ref, reactive, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePersistedForm } from '@/stores/formStore';
import 'v-calendar/style.css';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
import moment from 'moment';
import VCalendar from 'v-calendar';
import InputError from '@/Components/InputError.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { notify } from '@/utils/notify';

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

const productoOptions = ref(props.productos.map(p => ({ ...p, barcode: p.barcode ?? '' })));

const { form, reset } = usePersistedForm('compraForm', {
    id: props.compra !== undefined ? props.compra.id : null,
    proveedor: props.compra !== undefined ? props.compra.proveedor : '',
    fecha_compra: props.compra !== undefined ? new Date(props.compra.fecha_compra).toLocaleString('en-US', { timeZone: 'UTC' }) : new Date(),
    total_compra: props.compra !== undefined ? props.compra.total_compra : 0,
    status: props.compra !== undefined ? props.compra.status : '',
    productos: props.compra !== undefined ? props.compra.productos.map(p => ({ ...p, barcode: p.barcode ?? '' })) : [],
    abonos: props.compra !== undefined ? props.compra.abonos : [],
    total_credito: props.compra !== undefined ? props.compra.total_credito : 0,
    fecha_credito: props.compra !== undefined ? props.compra.fecha_credito : null,
    total_credito: props.compra !== undefined ? props.compra.total_credito : 0,
    producto: '',
    abono: '',
    cantidad_pedido: 1,
    abonoObj: {
        type: Object,
        default: () => ({})
    },
    precio_compra: 0,
    pagadaInicial: true
});

const statusOptions = [
    { value: 'adeudo', label: 'Adeudo' },
    { value: 'pagada', label: 'Pagada' },
    { value: 'retrasada', label: 'Retrasada' }
];
const statusClass = (status) => {
    switch (status) {
        case 'pagada':
            return 'bg-green-100 text-green-800';
        case 'adeudo':
            return 'bg-yellow-100 text-yellow-800';
        case 'retrasada':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};
const statusSeleccionado = ref(statusOptions.find(o => o.value === form.status) || null);
watch(statusSeleccionado, (v) => {
    form.status = v ? v.value : '';
    changeStatus();
});


const submit = () => {
    if (form.status == 'adeudo' && form.abonos.length == 0) {
        notify('Debe agregar al menos un abono', 'error');
        return;
    }
    if (props.compra == undefined) {
        form.post(route('compra.store'), {
            onSuccess: reset,
        });
    } else {
        form.put(route('compra.update', props.compra.id), {
            onSuccess: reset,
        });
    }
}

const addProducto = () => {
    let existe = false;
    form.productos.forEach((item, index) => {
        if (form.producto == null || form.producto.id === item.id) {
            existe = true;
            indexOf = index;
        }
    });
    if (existe && form.producto != null) {
        eliminarProducto(form.producto.id);
    }
    form.producto.cantidad = form.cantidad_pedido;
    form.producto.precio_compra = form.precio_compra;
    form.producto.subtotal = (form.cantidad_pedido * form.precio_compra).toFixed(2);
    form.total_compra += form.producto.subtotal;
    form.total_credito += form.producto.subtotal;
    parseFloat(form.producto.precio_compra).toFixed(2);
    parseFloat(form.producto.subtotal).toFixed(2);
    parseFloat(form.total_compra).toFixed(2);
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
    closeAddProductoModal();
};

const showProductoModal = ref(false);
const productoForm = reactive({
    nombre: '',
    id_clasificacion: '',
    id_marca: '',
    precio_unitario: 0,
    ieps: 0,
    precio_ieps: 0,
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

const productoIepsOptions = [
    { value: 0, label: '0%' },
    { value: 3, label: '3%' },
    { value: 6, label: '6%' },
    { value: 7, label: '7%' },
    { value: 9, label: '9%' }
];
const productoIepsSeleccionado = ref(null);
watch(productoIepsSeleccionado, (v) => {
    productoForm.ieps = v ? v.value : '';
    calcularIpsProducto();
});

const openProductoModal = () => {
    showProductoModal.value = true;
};

const closeProductoModal = () => {
    showProductoModal.value = false;
    productoForm.nombre = '';
    productoForm.id_clasificacion = '';
    productoForm.id_marca = '';
    productoForm.precio_unitario = 0;
    productoForm.ieps = 0;
    productoForm.precio_ieps = 0;
    productoForm.tamano = '';
    productoForm.ingrediente_activo = '';
    productoForm.barcode = '';
    productoErrors.value = {};
};

const calcularIpsProducto = () => {
    productoForm.precio_ieps = (((parseFloat(productoForm.precio_unitario) / 100) * parseFloat(productoForm.ieps)) + parseFloat(productoForm.precio_unitario)).toFixed(2);
};

const calcularPrecioCompraProducto = () => {
    productoForm.precio_unitario = (parseFloat(productoForm.precio_ieps) - ((parseFloat(productoForm.precio_ieps) / 100) * parseFloat(productoForm.ieps))).toFixed(2);
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
    }
}
</script>

<template>
    <AppLayout title="CrearCompra">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Crear Compra
            </h2>
        </template>
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mt-5">
            <div class="flex font-semibold text-xl dark:text-white-200 leading-tight w-full">
                <div class="grow">
                    <div class="md-col-span-2 mt-5 md:mt-0">
                        <div class="p-4 bg-white border border-gray-200 rounded-md shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <form @submit.prevent="submit">

                                <label class="block font-medium text-sm text-gray-700">Nombre del proveedor</label>
                                <input type="text" :disabled="props.compra !== undefined"
                                    class="form-input w-full rounded-md shadow-sm" v-model="form.proveedor">
                                <InputError class="mt-2" :message="form.errors.proveedor" />
                                <br>
                                <br>

                                <label>Productos: </label>

                                <button type="button" @click="openAddProductoModal"
                                    class="mt-2 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Agregar producto
                                </button>
                                <br>
                                <br>

                                <div class="relative overflow-x-auto shadow-sm sm:rounded-lg bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead
                                            class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th>Nombre del producto</th>
                                                <th>Cantidad del pedido</th>
                                                <th>Precio compra</th>
                                                <th>Subtotal</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="producto in form.productos" :key="producto.id" class="border-b dark:border-gray-700">
                                                <td class="px-4 py-2"> {{ producto.nombre }} </td>
                                                <td class="px-4 py-2"> {{ producto.cantidad }}</td>
                                                <td class="px-4 py-2"> {{ producto.precio_compra }}</td>
                                                <td class="px-4 py-2"> {{ producto.subtotal }}</td>
                                                <td class="px-4 py-2">
                                                    <div class="inline-flex rounded-md shadow-sm" role="group">
                                                        <button :disabled="props.compra !== undefined"
                                                            @click.prevent="eliminarProducto(producto.id)"
                                                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md
                                                                    hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                                                    focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                                                    dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <hr class="my-6">

                                <label class="block font-medium text-sm text-gray-700">Fecha de la compra</label>
                                <VDatePicker class="form-input w-full rounded-md shadow-sm"
                                    :max-date="props.compra !== undefined ? form.fecha_compra : null"
                                    :min-date="props.compra !== undefined ? form.fecha_compra : null"
                                    v-model="form.fecha_compra" expanded />
                                <InputError class="mt-2" :message="form.errors.fecha_compra" />
                                <br>

                                <label class="block font-medium text-sm text-gray-700">Total de la compra</label>
                                <input type="number" step="0.01" class="form-input w-full rounded-md shadow-sm" v-model="form.total_compra"
                                    disabled>
                                <InputError class="mt-2" :message="form.errors.total_compra" />
                                <br>

                                <label class="block font-medium text-sm text-gray-700">Total de credito</label>
                                <input type="number" step="0.01" class="form-input w-full rounded-md shadow-sm" v-model="form.total_credito"
                                    disabled>
                                <InputError class="mt-2" :message="form.errors.total_credito" />
                                <br>

                                <label class="block font-medium text-sm text-gray-700">Estatus de la compra</label>
                                <vue-single-select v-model="statusSeleccionado" :options="statusOptions" option-key="value" option-label="label" class="mt-1 w-full" :disabled="form.status == 'pagada' || props.compra !== undefined" />
                                <span v-if="form.status" :class="'mt-2 inline-block px-2 py-1 rounded-full text-xs capitalize ' + statusClass(form.status)">{{ form.status }}</span>
                                <InputError class="mt-2" :message="form.errors.status" />
                                <br>

                                <hr class="my-6">
                                <label class="block font-medium text-sm text-gray-700">Abonar a credito</label>
                                <input type="number" step="0.01" class="form-input w-full rounded-md shadow-sm"
                                    :disabled="props.compra !== undefined && form.status != 'adeudo'"
                                    v-model="form.abono">
                                <br>
                                <br>

                                <button @click.prevent="agregarAbono()"
                                    :disabled="props.compra !== undefined && form.status != 'adeudo'"
                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md
                                            hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                            focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                            dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Abonar
                                </button>
                                <br>
                                <br>

                                <div class="relative overflow-x-auto shadow-sm sm:rounded-lg bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead
                                            class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th>Fecha abono</th>
                                                <th>Cantidad abono</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="abono in form.abonos" :key="abono.id" class="border-b dark:border-gray-700">
                                                <td class="px-4 py-2"> {{ abono.created_at }}</td>
                                                <td class="px-4 py-2"> {{ abono.cantidad_abonada }} </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <br>
                                <br>

                                <hr class="my-6">

                                <button
                                    :disabled="props.compra !== undefined && form.status != 'adeudo' && form.pagadaInicial"
                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md
                                            hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                            focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                            dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Guardar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DialogModal :show="showAddProductoModal" @close="closeAddProductoModal">
            <template #title>
                Agregar producto
            </template>

            <template #content>
                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Cantidad de pedido</label>
                    <input type="number" min="1" step="0.01" class="form-input w-full rounded-md shadow-sm"
                        v-model="form.cantidad_pedido">
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">Nombre del producto</label>
                    <vue-single-select placeholder="Seleccione un producto" v-model="form.producto" option-key="barcode"
                        option-label="nombre" :options="productoOptions">
                    </vue-single-select>
                    <button type="button" @click="openProductoModal"
                        class="mt-2 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                        Crear producto
                    </button>
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">Precio de compra</label>
                    <input type="number" step="0.01" class="form-input w-full rounded-md shadow-sm"
                        v-model="form.precio_compra">
                </div>
            </template>

            <template #footer>
                <button @click="closeAddProductoModal"
                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                    Cancelar
                </button>
                <button @click="handleAddProducto"
                    class="ml-3 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                    Agregar
                </button>
            </template>
        </DialogModal>
        <DialogModal :show="showProductoModal" @close="closeProductoModal">
            <template #title>
                Crear producto
            </template>

            <template #content>
                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Nombre</label>
                    <input class="form-input w-full rounded-md shadow-sm" v-model="productoForm.nombre">
                    <InputError class="mt-2" :message="productoErrors.nombre" />
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">Clasificacion</label>
                    <vue-single-select v-model="productoClasificacionSeleccionada" :options="clasificaciones" option-key="id" option-label="nombre" placeholder="Selecione" class="w-full" />
                    <InputError class="mt-2" :message="productoErrors.id_clasificacion" />
                    <br>

                    <label class="block font-medium text-sm text-gray-700">Marca</label>
                    <vue-single-select v-model="productoMarcaSeleccionada" :options="marcas" option-key="id" option-label="nombre" placeholder="Selecione" class="w-full" />
                    <InputError class="mt-2" :message="productoErrors.id_marca" />
                    <br>

                    <label class="block font-medium text-sm text-gray-700">Precio Compra</label>
                    <input type="number" step="0.01" @change="calcularIpsProducto" class="form-input w-full rounded-md shadow-sm"
                        v-model="productoForm.precio_unitario">
                    <InputError class="mt-2" :message="productoErrors.precio_unitario" />
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">IEPS</label>
                    <vue-single-select v-model="productoIepsSeleccionado" :options="productoIepsOptions" option-key="value" option-label="label" placeholder="Selecione" class="w-full" />
                    <InputError class="mt-2" :message="productoErrors.ieps" />
                    <br>

                    <label class="block font-medium text-sm text-gray-700">Precio con ieps</label>
                    <input type="number" step="0.01" @change="calcularPrecioCompraProducto" class="form-input w-full rounded-md shadow-sm"
                        v-model="productoForm.precio_ieps">
                    <InputError class="mt-2" :message="productoErrors.precio_ieps" />
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">Tamaño</label>
                    <input class="form-input w-full rounded-md shadow-sm" v-model="productoForm.tamano">
                    <InputError class="mt-2" :message="productoErrors.tamano" />
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">Ingrediente activo</label>
                    <input class="form-input w-full rounded-md shadow-sm" v-model="productoForm.ingrediente_activo">
                    <InputError class="mt-2" :message="productoErrors.ingrediente_activo" />
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">Código de barras</label>
                    <input class="form-input w-full rounded-md shadow-sm" v-model="productoForm.barcode">
                    <InputError class="mt-2" :message="productoErrors.barcode" />
                </div>
            </template>

            <template #footer>
                <button @click="closeProductoModal"
                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                    Cancelar
                </button>
                <button @click="guardarProducto"
                    class="ml-3 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                    Guardar
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
