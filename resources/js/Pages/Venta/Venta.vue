<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, onMounted, onUnmounted, reactive, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';

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
    },
    productosSucursal: {
        type: Array,
        default: []
    },
});

const productosFiltrados = computed(() => props.productos.map(p => ({ ...p, barcode: p.barcode ?? '' })));

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

const canAddProducto = computed(() =>
    form.cliente && form.cliente.id &&
    form.producto && form.producto.id &&
    form.cantidad >= 1
);

const q = ref('');

watch(q, (value) => {
    router.get(route('venta.index', { q: value }), {}, { preserveState: true });
});

const b = ref('');

watch(b, (value) => {
    router.get(route('venta.index', { b: value }), {}, { preserveState: true });
});

const agregarVenta = () => {
    if (!canAddProducto.value) {
        alert('Seleccione un cliente, un producto y una cantidad mínima de 1.');
        return false;
    }
    if (form.producto.cantidad < form.cantidad) {
        alert("No tienes esa cantidad en stock, tu tienes " + form.producto.cantidad + " en bodega");
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

const printTicketSilently = (url, callback = () => { }) => {
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.src = url;
    iframe.onload = () => {
        if (iframe.contentWindow) {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            iframe.contentWindow.onafterprint = () => {
                document.body.removeChild(iframe);
                callback();
            };
        }
    };
    document.body.appendChild(iframe);
};

const finalizeSale = () => {
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
                    const url = route('venta.ticket.html', { venta: data.props.venta.id }) + '?size=80';
                    printTicketSilently(url, () => {
                        location.replace('/venta');
                    });
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

// Estado del modal
const isModalOpen = ref(false);

// Función para manejar la tecla F2
const handleKeydown = (event) => {
    if (event.key === "F2") {
        event.preventDefault(); // Evita acciones predeterminadas del navegador
        isModalOpen.value = true;
    }
};

// Agregar y remover el evento cuando el componente se monta/desmonta
onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeydown);
});

// Función para cerrar el modal
const closeModal = () => {
    isModalOpen.value = false;
};
</script>

<style scoped>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
}
</style>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Venta de agroquimicos
            </h2>
        </template>

        <hr class="my-6">


        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grow">
                <div class="shadow bg-white md:rounded-md p-4 md-col-span-2 mt-5 md:mt-0">

                    <div style=" text-align: left;">
                        <h6>Selecciona un cliente</h6>
                    </div>


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label>Cliente: </label>
                        <vue-single-select id="singleTest" placeholder="Seleccione un cliente" v-model="form.cliente"
                            option-key="id" option-label="nombre" @input="changeClient($event)"
                            :class="[selectClient ? 'pointer-events-none' : '', 'w-full max-w-full']" :options="clientes">
                        </vue-single-select>

                        <label>Porcentaje de descuento para cliente: </label>
                        <input type="text" readonly v-model="form.porcentaje_descuento" :disabled="true"
                            class="orm-input rounded-md shadow-sm w-full max-w-full bg-gray-100 cursor-not-allowed" />
                        <br>

                    </div>

                    <div>
                        <hr class="my-6">

                        <div style="text-align: center;">
                            <h6>Ticket de venta</h6>
                        </div>
                        <br>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5 ml-auto">
                            <label class="block font-medium text-sm text-gray-700">Tipo de venta</label>
                            <select v-model="formVenta.tipoVenta" id="tipoVenta" name="tipoVenta" class="mt-1 block w-full max-w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="" disabled>Selecione</option>
                                <option value="Contado">Contado</option>
                                <option value="Credito">Credito</option>
                            </select>
                            <br>

                            <div v-if="formVenta.tipoVenta === 'Credito'">
                                <label>abono a cuenta: </label>
                                <input type="number" step="0.01" v-model="form.abono"
                                    class="form-input rounded-md shadow-sm w-full max-w-full" />
                            </div>
                        </div>
                        <div class="md:hidden grid grid-cols-1 gap-4 w-full mb-4">
                            <div v-for="producto in productoVenta" :key="producto.producto.id" class="rounded-lg boder p-4 bg-white shadow-lg">
                                <div class="text-sm text-gray-500">Nombre del producto</div>
                                <div class="font-semibold text-gray-900">{{ producto.producto.nombre }}</div>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <div class="text-gray-500">Cantidad</div>
                                        <div>{{ producto.cantidad }}</div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Precio unitario</div>
                                        <div>{{ producto.precio_unitario }}</div>
                                    </div>
                                    <div class="col-span-2">
                                        <div class="text-gray-500">Importe</div>
                                        <div>{{ producto.importe }}</div>
                                    </div>
                                </div>
                                <div class="flex justify-end mt-3">
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
                                </div>
                            </div>
                        </div>

                        <div class="relative overflow-x-auto hidden md:block w-full shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs uppercase bg-gray-50">
                                    <tr class="[&>th]:px-4 [&>th]:py-3">
                                        <th>Id</th>
                                        <th>Nombre del producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio unitario</th>
                                        <th>Importe</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="producto in productoVenta" :value="producto.producto.id"
                                        :key="producto.producto.id">
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
                          </div>

                        <br><br>

                        <hr>
                        </hr>
                        <div class="w-full flex flex-col md:flex-row justify-between mb-4 mt-4 px-4 gap-4">
                            <button @click="finalizeSale()"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                        hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                        focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                        dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Terminar venta
                            </button>
                            <label class="font-bold">Total final: $ {{ total }} </label>

                        </div>

                            <div style=" text-align: left;" class="mb-4 mt-3 w-full flex flex-col md:flex-row justify-between">
                                <h6>Agregar productos de venta</h6>
                                <span>F2: Buscar en sucursal</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label>Productos: </label>
                                    <vue-single-select placeholder="Seleccione un producto" v-model="form.producto"
                                      option-key="barcode" option-label="nombre" @input="handleSelectChange($event)"
                                      :options="productosFiltrados" class="w-full max-w-full">
                                  </vue-single-select>
                              </div>

                              <div>
                                  <label>Cantidad de productos: </label>
                                  <input type="number" min="1" step="0.01" v-model="form.cantidad"
                                      @input="changeQuantity($event)" class="form-input rounded-md shadow-sm w-full max-w-full" />
                                  <br>
                              </div>

                              <div>
                                  <label>Precio unitario: </label>
                                  <input type="number" step="0.01" readonly v-model="form.precio_ieps_con_descuento"
                                      :disabled="true"
                                      class="orm-input rounded-md shadow-sm w-full max-w-full bg-gray-100 cursor-not-allowed" />
                                  <br>
                              </div>

                              <div>
                                  <label>importe: </label>
                                  <input type="number" step="0.01" readonly v-model="form.importe" :disabled="true"
                                      class="orm-input rounded-md shadow-sm w-full max-w-full bg-gray-100 cursor-not-allowed" />
                                  <br>
                              </div>
                          </div>
                        <br>
                        <div>
                            <button @click="agregarVenta()" :class="{ 'opacity-25': !canAddProducto }"
                                :disabled="!canAddProducto"
                                class="text-blue-700 hover:text-white border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2 dark:border-blue-500 dark:text-blue-500 dark:hover:text-white dark:hover:bg-blue-500 dark:focus:ring-blue-800 w-full">
                                Agregar producto
                            </button>
                        </div>

                        <br>

                        <br>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 w-lg">
            <div class="bg-white p-6 rounded-lg shadow-lg w-lg">
                <h2 class="text-xl font-semibold mb-4">Busqueda en sucursales</h2>

                <!-- Input de búsqueda -->
                 <input v-model="b" type="text" placeholder="Buscar producto..."
                    class="w-full max-w-full p-2 border rounded-md focus:ring focus:ring-blue-300" />

                <!-- Tabla de productos -->
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-6">Producto</th>
                                <th class="border p-6">Marca</th>
                                <th class="border p-6">Sucursal</th>
                                <th class="border p-6">Cantidad en stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="producto in props.productosSucursal" :key="producto.id">
                                <td class="border p-6">{{ producto?.nombre }}</td>
                                <td class="border p-6">{{ producto?.marca }}</td>
                                <td class="border p-6">{{ producto?.sucursal?.nombre }}</td>
                                <td class="border p-6 text-center">{{ producto?.cantidad }}</td>
                            </tr>
                            <tr v-if="props.productosSucursal.length === 0">
                                <td colspan="4" class="border p-6 text-center text-gray-500">
                                    No se encontraron productos
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Botón para cerrar -->
                <div class="mt-4 flex justify-end">
                    <button @click="closeModal"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
