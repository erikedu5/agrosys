
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import 'v-calendar/style.css';
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';
    import moment from 'moment';
    import VCalendar from 'v-calendar';

    const props=defineProps({
        compra: Object,
        productos: {
            type: Array,
            default: []
        },
    });

    const form = useForm({
        id: props.compra !== undefined ? props.compra.id: null,
        proveedor: props.compra !== undefined ? props.compra.proveedor : '',
        fecha_compra: props.compra !== undefined ? new Date(props.compra.fecha_compra).toLocaleString('en-US', { timeZone: 'UTC' }) : new Date(),
        total_compra: props.compra !== undefined ? props.compra.total_compra: 0,
        status: props.compra !== undefined ? props.compra.status: '',
        productos: props.compra !== undefined ? props.compra.productos: [],
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
        form.producto.subtotal = form.cantidad_pedido *  form.precio_compra;
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

    const agregarAbono = () => {
        form.abonoObj.cantidad_abonada = parseFloat(form.abono).toFixed(2);
        form.abonoObj.created_at = moment(new Date()).format('YYYY-MM-DD hh:mm:ss');
        form.abonos.push(form.abonoObj);
        console.table(form.abonos);
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
                            <br>
                            <br>

                            <label>Productos: </label>

                            <label class="block font-medium text-sm text-gray-700">Cantidad de pedido</label>
                            <input :disabled="props.compra !== undefined"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.cantidad_pedido">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Nombre del producto</label>
                            <vue-single-select :disabled="props.compra !== undefined"
                                placeholder="Seleccione un producto"
                                v-model="form.producto"
                                option-key="id"
                                option-label="nombre"
                                :options="productos">
                            </vue-single-select>
                            <br>


                            <label class="block font-medium text-sm text-gray-700">Precio de compra</label>
                            <input :disabled="props.compra !== undefined"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.precio_compra">
                            <br>
                            <br>

                            <button :disabled="props.compra !== undefined"
                            @click.prevent="addProducto($event)"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                            hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                            focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                            dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Agregar producto
                            </button>
                            <br>
                            <br>

                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                        <th>Id</th>
                                        <th>Nombre del producto</th>
                                        <th>Ingrediente activo</th>
                                        <th>Tamaño</th>
                                        <th>Marca</th>
                                        <th>Cantidad del pedido</th>
                                        <th>Precio Compra</th>
                                        <th>Subtotal</th>
                                        <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="producto in form.productos" :key="producto.id">
                                            <td class="px-4 py-2"> {{ producto.id }}</td>
                                            <td class="px-4 py-2"> {{ producto.nombre }} </td>
                                            <td class="px-4 py-2"> {{ producto.ingrediente_activo }} </td>
                                            <td class="px-4 py-2"> {{ producto.tamano }} </td>
                                            <td class="px-4 py-2"> {{ producto.marca.nombre }}</td>
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

                            <div v-if="$page.props.errors.proveedor" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline"> {{ $page.props.errors.proveedor }}</span>
                            </div>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Fecha de la compra</label>
                            <VDatePicker class="form-input w-full rounded-md shadow-sm"
                                :max-date="props.compra !== undefined? form.fecha_compra: null"
                                :min-date="props.compra !== undefined? form.fecha_compra: null"
                                v-model="form.fecha_compra" expanded />
                            <br>
                            <div v-if="$page.props.errors.fecha_compra" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline"> {{ $page.props.errors.fecha_compra }}</span>
                            </div>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Total de la compra</label>
                            <input
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.total_compra" disabled>
                            <br>
                            <div v-if="$page.props.errors.total_compra" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline"> {{ $page.props.errors.total_compra }}</span>
                            </div>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Total de credito</label>
                            <input
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.total_credito" disabled>
                            <br>
                            <div v-if="$page.props.errors.total_credito" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline"> {{ $page.props.errors.total_credito }}</span>
                            </div>
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

                            <div v-if="$page.props.errors.status" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <br>
                                <span class="block sm:inline"> {{ $page.props.errors.status }}</span>
                            </div>
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
    </AppLayout>
</template>
