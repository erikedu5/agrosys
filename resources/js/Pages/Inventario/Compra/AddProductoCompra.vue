
<script setup>
    import { ref, reactive } from 'vue';
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import 'v-calendar/style.css';
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';
    import moment from 'moment';
    import VCalendar from 'v-calendar';
    import InputError from '@/Components/InputError.vue';
    import DialogModal from '@/Components/DialogModal.vue';

    const props=defineProps({
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

    const form = useForm({
        id: props.compra !== undefined ? props.compra.id: null,
        proveedor: props.compra !== undefined ? props.compra.proveedor : '',
        fecha_compra: props.compra !== undefined ? new Date(props.compra.fecha_compra).toLocaleString('en-US', { timeZone: 'UTC' }) : new Date(),
        total_compra: props.compra !== undefined ? props.compra.total_compra: 0,
        status: props.compra !== undefined ? props.compra.status: '',
        productos: props.compra !== undefined ? props.compra.productos.map(p => ({ ...p, barcode: p.barcode ?? '' })) : [],
        abonos: props.compra !== undefined ? props.compra.abonos: [],
        total_credito: props.compra !== undefined ? props.compra.total_credito: 0,
        fecha_credito: props.compra !== undefined ? props.compra.fecha_credito: null,
        total_credito: props.compra !== undefined ? props.compra.total_credito: 0,
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

    const submit = () => {
        if (form.status == 'adeudo' && form.abonos.length == 0) {
            alert('Debe agregar al menos un abono');
            return;
        }
        if (props.compra == undefined) {
            form.post(route('compra.store'), form);
        } else {
            form.put(route('compra.update', props.compra.id), form);
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
        form.producto.subtotal = (form.cantidad_pedido *  form.precio_compra).toFixed(2);
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
            if(id === item.id) {
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

        <div class="flex font-semibold text-xl dark:text-white-200 leading-tight">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">

                            <label class="block font-medium text-sm text-gray-700">Nombre del proveedor</label>
                            <input type="text" :disabled="props.compra !== undefined"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.proveedor">
                            <InputError class="mt-2" :message="form.errors.proveedor" />
                            <br>
                            <br>

                            <label>Productos: </label>

                            <button type="button" @click="openAddProductoModal"
                                class="mt-2 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Agregar producto
                            </button>
                            <br>
                            <br>

                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                        <th>Nombre del producto</th>
                                        <th>Cantidad del pedido</th>
                                        <th>Precio compra</th>
                                        <th>Subtotal</th>
                                        <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="producto in form.productos" :key="producto.id">
                                            <td class="px-4 py-2"> {{ producto.nombre }} </td>
                                            <td class="px-4 py-2"> {{ producto.cantidad }}</td>
                                            <td class="px-4 py-2"> {{ producto.precio_compra }}</td>
                                            <td class="px-4 py-2"> {{ producto.subtotal }}</td>
                                            <td class="px-4 py-2">
                                                <div class="inline-flex rounded-md shadow-sm" role="group">
                                                    <button :disabled="props.compra !== undefined"
                                                    @click.prevent="eliminarProducto(producto.id)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
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
                                :max-date="props.compra !== undefined? form.fecha_compra: null"
                                :min-date="props.compra !== undefined? form.fecha_compra: null"
                                v-model="form.fecha_compra" expanded />
                            <InputError class="mt-2" :message="form.errors.fecha_compra" />
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Total de la compra</label>
                            <input
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.total_compra" disabled>
                            <InputError class="mt-2" :message="form.errors.total_compra" />
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Total de credito</label>
                            <input
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.total_credito" disabled>
                            <InputError class="mt-2" :message="form.errors.total_credito" />
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Estatus de la compra</label>
                            <select v-model="form.status" id="status" name="ieps"
                             class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    :disabled="form.status == 'pagada' || props.compra !== undefined"
                                    @change="changeStatus($event)">
                            <option value="" disabled>Selecione</option>
                            <option value="adeudo">Adeudo</option>
                            <option value="pagada">Pagada</option>
                            <option value="pagada" :disabled="form.compra == undefined">Retrasada</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                            <br>

                            <hr class="my-6">
                              <label class="block font-medium text-sm text-gray-700">Abonar a credito</label>
                            <input class="form-input w-full rounded-md shadow-sm"
                                :disabled="props.compra !== undefined && form.status != 'adeudo'"
                                v-model="form.abono">
                            <br>
                            <br>

                            <button
                            @click.prevent="agregarAbono()"
                            :disabled="props.compra !== undefined && form.status != 'adeudo'"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                            hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                            focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                            dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Abonar
                            </button>
                            <br>
                            <br>

                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                        <th>Fecha abono</th>
                                        <th>Cantidad abono</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="abono in form.abonos" :key="abono.id">
                                            <td class="px-4 py-2"> {{ abono.created_at}}</td>
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
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                            hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                            focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                            dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Guardar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="flex-none w-14 h-14">
            </div>
        </div>
        <DialogModal :show="showAddProductoModal" @close="closeAddProductoModal">
            <template #title>
                Agregar producto
            </template>

            <template #content>
                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Cantidad de pedido</label>
                    <input  type="number" min="1" class="form-input w-full rounded-md shadow-sm" v-model="form.cantidad_pedido">
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">Nombre del producto</label>
                    <vue-single-select placeholder="Seleccione un producto"
                        v-model="form.producto"
                        option-key="barcode"
                        option-label="nombre"
                        :options="productoOptions">
                    </vue-single-select>
                    <button type="button" @click="openProductoModal"
                        class="mt-2 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                        Crear producto
                    </button>
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">Precio de compra</label>
                    <input type="number" step="0.01" class="form-input w-full rounded-md shadow-sm" v-model="form.precio_compra">
                </div>
            </template>

            <template #footer>
                <button @click="closeAddProductoModal"
                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                    Cancelar
                </button>
                <button @click="handleAddProducto" class="ml-3 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
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
                    <select v-model="productoForm.id_clasificacion"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="" disabled>Selecione</option>
                        <option v-for="clasificacion in clasificaciones" :value="clasificacion.id" :key="clasificacion.id">
                            {{ clasificacion.nombre }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="productoErrors.id_clasificacion" />
                    <br>

                    <label class="block font-medium text-sm text-gray-700">Marca</label>
                    <select v-model="productoForm.id_marca"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="" disabled>Selecione</option>
                        <option v-for="marca in marcas" :value="marca.id" :key="marca.id">
                            {{ marca.nombre }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="productoErrors.id_marca" />
                    <br>

                    <label class="block font-medium text-sm text-gray-700">Precio Compra</label>
                    <input @change="calcularIpsProducto" class="form-input w-full rounded-md shadow-sm" v-model="productoForm.precio_unitario">
                    <InputError class="mt-2" :message="productoErrors.precio_unitario" />
                    <br><br>

                    <label class="block font-medium text-sm text-gray-700">IEPS</label>
                    <select v-model="productoForm.ieps" @change="calcularIpsProducto"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="" disabled>Selecione</option>
                        <option value="0" default>0%</option>
                        <option value="3">3%</option>
                        <option value="6">6%</option>
                        <option value="7">7%</option>
                        <option value="9">9%</option>
                    </select>
                    <InputError class="mt-2" :message="productoErrors.ieps" />
                    <br>

                    <label class="block font-medium text-sm text-gray-700">Precio con ieps</label>
                    <input @change="calcularPrecioCompraProducto" class="form-input w-full rounded-md shadow-sm" v-model="productoForm.precio_ieps">
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
                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                    Cancelar
                </button>
                <button @click="guardarProducto" class="ml-3 px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                    Guardar
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
