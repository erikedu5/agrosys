<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch } from 'vue';
    import { router, useForm } from '@inertiajs/vue3';
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';
    import { reactive } from 'vue';

    const productoVenta = reactive([]);
    let total = 0.0;
    let selectClient = false;
    let venta_id = 0;

    const props = defineProps({
        productos: {
            type: Array,
            default: []
        },
        producto_id: {
            type: Object,
            default: 0
        },
        clientes: {
            type: Array,
            default: []
        }
    });

    let form = useForm({
        cantidad: 1,
        importe: 0,
        porcentaje_descuento: 0,
        precio_ieps_con_descuento: 0,
        producto: {},
        cliente: {},
        abono: 0
    });

    const formVenta = useForm({
        productoVenta: {},
        clientVenta: {},
        tipoVenta: "",
        total: 0
    });

    const q = ref('');

    watch(q, (value) => {
        router.get( route( 'venta.index', { q: value } ), {}, { preserveState: true } );
    });

    const agregarVenta = () => {
        if (form.producto.cantidad < form.cantidad) {
            alert("No tienes esa cantidad en stock, tu tienes " + form.producto.cantidad + " en bodega");
            return false;
        }
        if (form.producto.nombre == undefined ) {
            alert("Selecciona al menos un producto");
            return false;
        }
        if(form.precio_ieps_con_descuento == "NaN") {
            alert("Seleccione un cliente primero");
            return false;
        }

        let venta = {
            'producto': form.producto,
            'cantidad': form.cantidad,
            'precio_unitario': form.precio_ieps_con_descuento,
            'importe': form.importe
        };
        productoVenta.push(venta);
        total = (parseFloat(total) + parseFloat(form.importe)).toFixed(2);
        let cliente = form.cliente;
        form.reset();
        form.producto = 0;
        form.cliente = cliente;
        form.porcentaje_descuento = cliente.porcentaje_descuento;
        selectClient = true;
    };

    const changeQuantity = () => {
        if (form.cantidad > form.producto.cantidad) {
            alert("No tienes esa cantidad en stock, tu tienes " + form.producto.cantidad + " en bodega");
            return false;
        }
        form.importe = form.cantidad * form.precio_ieps_con_descuento;
        form.importe = form.importe.toFixed(2);
    }

    const finalizeSale = () => {
        if (form.cliente.id == undefined) {
            alert("Debe seleccionar un cliente");
            return false;
        }
        if (formVenta.tipoVenta == "") {
            alert("Debe seleccionar tipo de venta");
            return false;
        }
        if (productoVenta.length == 0) {
            alert("Debe seleccionar al menos un producto a la venta");
            return false;
        }
        if (productoVenta.length !== 0) {
            form.post(route('venta.store',
                {
                    'id_cliente': form.cliente.id,
                    'total': total,
                    'producto_venta': productoVenta,
                    'tipo_venta': formVenta.tipoVenta + "",
                    'abono': form.abono
                }),
            {
                preserveState: true,
                onSuccess: (data) => {
                    let popup  = window.open( "_blank");
                    popup.location = '/ticket/' + data.props.venta.id;
                    location.replace('/dashboard');
                },
                onError: (errors) => {
                    console.error(errors);
                }
            });
        }
    }


    const handleSelectChange = (event) => {
        if (event !== null) {
            if (form.producto.cantidad < form.cantidad) {
                alert("No tienes esa cantidad en stock, tu tienes " + form.producto.cantidad + " en bodega");
                return false;
            }
            form.precio_ieps_con_descuento = (form.producto.precio_ieps
                                            - ((form.producto.precio_ieps / 100)
                                                * form.cliente.porcentaje_descuento)).toFixed(2);
            form.importe = (form.precio_ieps_con_descuento * form.cantidad).toFixed(2);
        } else {
            let cliente = form.cliente;
            form.reset();
            form.producto = 0;
            form.cliente = cliente;
            form.porcentaje_descuento = cliente.porcentaje_descuento;
            selectClient = true;
        }
    }

    const changeClient = (event) => {
        if (event !== null) {
            form.porcentaje_descuento = form.cliente.porcentaje_descuento;
            if (form.producto !== null) {
                form.precio_ieps_con_descuento = (form.producto.precio_ieps
                                                    - ((form.producto.precio_ieps / 100)
                                                            * form.cliente.porcentaje_descuento)).toFixed(2);
                form.importe = (form.precio_ieps_con_descuento * form.cantidad).toFixed(2);
            }
        }
    }

    const actualizarProducto = (producto) => {
        form.producto = producto.producto;
        form.cantidad = producto.cantidad;
        eliminarProducto(producto);
    }

    const eliminarProducto = (producto) => {
        total = (total - parseFloat(producto.importe)).toFixed(2);
        const index = productoVenta.indexOf(producto);
        productoVenta.splice(index, 1);
    }

</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Venta de agroquimicos
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">
                <div  class="shadow bg-white md:rounded-md p-4 md-col-span-2 mt-5 md:mt-0">

                    <div style=" text-align: center;">
                        <h6>Agregar productos de venta</h6>
                    </div>


                    <div class="columns-2">
                        <label>Cliente: </label>
                        <vue-single-select
                            id = "singleTest"
                            placeholder="Seleccione un cliente"
                            v-model="form.cliente"
                            option-key="id"
                            option-label="nombre"
                            @input="changeClient($event)"
                            :class="selectClient ? 'pointer-events-none': '' "
                            :options="clientes">
                        </vue-single-select>
                        <br>

                        <label>Productos: </label>
                        <vue-single-select
                            placeholder="Seleccione un producto"
                            v-model="form.producto"
                            option-key="id"
                            option-label="nombre"
                            @input="handleSelectChange($event)"
                            :options="productos">
                        </vue-single-select>
                        <br>

                        <label>Cantidad de productos: </label>
                        <input type="number" min="1" v-model="form.cantidad"
                               @input="changeQuantity($event)"
                               class="form-input rounded-md shadow-sm w-full"/>
                        <br><br>


                        <label>Porcentaje de descuento para cliente: </label>
                        <input type="text" readonly v-model="form.porcentaje_descuento"
                               class="form-input rounded-md shadow-sm w-full"/>
                        <br><br>

                        <label>Precio unitario: </label>
                        <input type="number" readonly v-model="form.precio_ieps_con_descuento"
                               class="form-input rounded-md shadow-sm w-full"/>
                        <br><br>

                        <label>importe: </label>
                        <input type="number" readonly v-model="form.importe"
                               class="form-input rounded-md shadow-sm w-full"/>
                        <br><br>

                    </div>
                    <div>
                        <button @click="agregarVenta()"
                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Agregar producto
                        </button>
                    </div>
                    <div>
                        <hr class="my-6">

                        <div style="text-align: center;">
                            <h6>Ticket de venta</h6>
                        </div>
                        <br>

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                    <th>Id</th>
                                    <th>Nombre del producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio unitario</th>
                                    <th>Importe</th>
                                    <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="producto in productoVenta" :value="producto.producto.id" :key="producto.producto.id">
                                        <td class="px-4 py-2"> {{ producto.producto.id }}</td>
                                        <td class="px-4 py-2"> {{ producto.producto.nombre }} </td>
                                        <td class="px-4 py-2"> {{ producto.cantidad }} </td>
                                        <td class="px-4 py-2"> {{ producto.precio_unitario }} </td>
                                        <td class="px-4 py-2"> {{ producto.importe }} </td>
                                        <td class="px-4 py-2">
                                            <div class="inline-flex rounded-md shadow-sm" role="group">
                                                <button @click="actualizarProducto(producto)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                     Actualizar
                                                </button>

                                                <button @click="eliminarProducto(producto)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <br><br>

                            <label>Total final: {{ total }} </label>
                            <br><br>

                        </div>
                        <br>
                        <div class="columns-2">
                            <label class="block font-medium text-sm text-gray-700">Tipo de venta</label>
                            <select v-model="formVenta.tipoVenta" id="tipoVenta" name="tipoVenta"
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="" disabled>Selecione</option>
                            <option value="Contado">Contado</option>
                            <option value="Credito">Credito</option>
                            </select>
                            <br>

                            <div v-if="formVenta.tipoVenta === 'Credito'">
                                <label>abono a cuenta: </label>
                                <input type="number" v-model="form.abono"
                                    class="form-input rounded-md shadow-sm w-full"/>
                            </div>
                        </div>
                        <br>
                        <div>
                            <button @click="finalizeSale()"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                    hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                    focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                    dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Terminar venta
                            </button>
                        </div>

                        <br>
                    </div>
                </div>
            </div>
            <div class="flex-none w-14 h-14">
            </div>
        </div>
    </AppLayout>
</template>
