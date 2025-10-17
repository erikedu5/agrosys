<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, onMounted, onUnmounted, reactive, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
import { notify } from '@/utils/notify';

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
        default: {}
    },
    clientes: {
        type: Array,
        default: []
    },
    productosSucursal: {
        type: Array,
        default: []
    },
    clientePublicoDefault: {
        type: Number,
        default: null
    },
    tipoVentaDefault: {
        type: String,
        default: 'contado'
    },
    ventasBloqueadas: {
        type: Boolean,
        default: false
    },
    motivoBloqueo: {
        type: String,
        default: null
    },
});

const bloqueoManual = ref(false);
const bloqueoActivo = computed(() => Boolean(props.ventasBloqueadas) || bloqueoManual.value);
const mensajeBloqueo = computed(() => props.motivoBloqueo || 'VEsta sección está bloqueada, Contacte a su administrador.');

watch(bloqueoActivo, (value) => {
    if (value) {
        notify(mensajeBloqueo.value, 'error');
    }
}, { immediate: true });

const productosFiltrados = computed(() => props.productos.map(p => ({ ...p, barcode: p.barcode ?? '', nombre: p.nombre + " - " + p.tamano })));

let form = useForm({
    cantidad: 1,
    importe: 0,
    porcentaje_descuento: 0,
    precio_ieps_con_descuento: 0,
    producto: {},
    cliente: {},
    abono: 0
});

const mensajeBloqueoUI = computed(() => {
    if (bloqueoActivo.value) {
        return form.errors.bloqueo || mensajeBloqueo.value;
    }

    return form.errors.bloqueo || null;
});

const formVenta = useForm({
    productoVenta: {},
    clientVenta: {},
    tipoVenta: "",
    total: 0
});

// Estado del modal de búsqueda de precio
const isPriceSearchModalOpen = ref(false);
const priceSearchQuery = ref('');
const priceSearchResults = ref([]);
const isSearching = ref(false);

const tipoVentaOptions = [
    { value: 'Contado', label: 'Contado' },
    { value: 'Credito', label: 'Credito' }
];
const tipoVentaSeleccionado = ref(tipoVentaOptions.find(o => o.value === formVenta.tipoVenta) || null);
watch(tipoVentaSeleccionado, (v) => {
    formVenta.tipoVenta = v ? v.value : '';
});

// Watcher para búsqueda de precio en tiempo real
watch(priceSearchQuery, (newQuery) => {
    if (newQuery && newQuery.trim().length > 0) {
        searchProductPrice();
    } else {
        priceSearchResults.value = [];
    }
});

const canAddProducto = computed(() =>
    !bloqueoActivo.value &&
    form.cliente && form.cliente.id &&
    form.producto && form.producto.id
);

const q = ref('');

const clientSelected = ref(true);

watch(q, (value) => {
    router.get(route('venta.index', { q: value }), {}, { preserveState: true });
});

const b = ref('');

watch(b, (value) => {
    router.get(route('venta.index', { b: value }), {}, { preserveState: true });
});

const recalculateTotal = () => {
    total = productoVenta.reduce((sum, p) => sum + parseFloat(p.importe), 0).toFixed(2);
};

const agregarVenta = () => {
    if (bloqueoActivo.value) {
        return false;
    }
    if (!canAddProducto.value) {
        return false;
    }
    const existente = productoVenta.find(p => p.producto.id === form.producto.id);
    const cantidadTotal = (existente ? existente.cantidad : 0) + form.cantidad;
    if (form.producto.cantidad < cantidadTotal) {
        notify('No tienes esa cantidad en stock, tu tienes ' + form.producto.cantidad + ' en bodega', 'error');
        return false;
    }
    if (existente) {
        existente.cantidad += form.cantidad;
        existente.importe = (existente.cantidad * existente.precio_unitario).toFixed(2);
    } else {
        let venta = {
            'producto': form.producto,
            'cantidad': form.cantidad,
            'precio_unitario': form.precio_ieps_con_descuento,
            'importe': form.importe
        };
        productoVenta.push(venta);
    }
    recalculateTotal();
    let cliente = form.cliente;
    form.reset();
    form.producto = 0;
    form.cliente = cliente;
    form.porcentaje_descuento = cliente.porcentaje_descuento;
    selectClient = true;
};

const updateQuantity = (producto) => {
    if (producto.cantidad === null || producto.cantidad === '') {
        producto.importe = 0;
        recalculateTotal();
        return;
    }
    if (producto.cantidad > producto.producto.cantidad) {
        notify('No tienes esa cantidad en stock, tu tienes ' + producto.producto.cantidad + ' en bodega', 'error');
        producto.cantidad = producto.producto.cantidad;
    }
    if (producto.cantidad < 1) {
        producto.cantidad = 1;
    }
    producto.importe = (producto.cantidad * producto.precio_unitario).toFixed(2);
    recalculateTotal();
};

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
    if (bloqueoActivo.value) {
        return;
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
                    const url = route('venta.ticket.html', { venta: data.props.venta.id }) + '?size=80';
                    printTicketSilently(url, () => {
                        location.replace('/venta');
                    });
                },
                onError: (errors) => {
                    if (errors?.bloqueo) {
                        bloqueoManual.value = true;
                    }
                    console.error(errors);
                }
            });
    }
}


const handleSelectChange = (event) => {
    if (event !== null) {
        form.precio_ieps_con_descuento = (form.producto.precio_ieps
            - ((form.producto.precio_ieps / 100)
                * form.cliente.porcentaje_descuento)).toFixed(2);
        form.importe = (form.precio_ieps_con_descuento * form.cantidad).toFixed(2);
        agregarVenta();
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
        clientSelected.value = false;
        form.porcentaje_descuento = form.cliente.porcentaje_descuento;
        if (form.producto !== null) {
            form.precio_ieps_con_descuento = (form.producto.precio_ieps
                - ((form.producto.precio_ieps / 100)
                    * form.cliente.porcentaje_descuento)).toFixed(2);
            form.importe = (form.precio_ieps_con_descuento * form.cantidad).toFixed(2);
        }
    }
}

const eliminarProducto = (producto) => {
    const index = productoVenta.indexOf(producto);
    productoVenta.splice(index, 1);
    recalculateTotal();
}

// Estado del modal de búsqueda en sucursales
const isModalOpen = ref(false);

// Función para manejar las teclas F2 y F3
const handleKeydown = (event) => {
    if (bloqueoActivo.value) {
        return;
    }
    if (event.key === "F2") {
        event.preventDefault(); // Evita acciones predeterminadas del navegador
        isModalOpen.value = true;
    } else if (event.key === "F3") {
        event.preventDefault(); // Evita acciones predeterminadas del navegador
        isPriceSearchModalOpen.value = true;
        // Limpiar resultados anteriores
        priceSearchResults.value = [];
        priceSearchQuery.value = '';
        // Focus en el input después de un pequeño delay para que el modal se renderice
        setTimeout(() => {
            const searchInput = document.getElementById('price-search-input');
            if (searchInput) {
                searchInput.focus();
            }
        }, 100);
    } else if (event.key === "Escape") {
        event.preventDefault();
        // Cerrar cualquier modal que esté abierto
        if (isModalOpen.value) {
            closeModal();
        }
        if (isPriceSearchModalOpen.value) {
            closePriceSearchModal();
        }
    }
};

// Agregar y remover el evento cuando el componente se monta/desmonta
onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
    
    // Establecer valores por defecto
    if (props.clientePublicoDefault && props.clientes.length > 0) {
        const clientePublico = props.clientes.find(c => c.id === props.clientePublicoDefault);
        if (clientePublico) {
            form.cliente = clientePublico;
            form.porcentaje_descuento = clientePublico.porcentaje_descuento;
        }
    }
    
    if (props.tipoVentaDefault) {
        const tipoDefault = tipoVentaOptions.find(o => o.value.toLowerCase() === props.tipoVentaDefault.toLowerCase());
        if (tipoDefault) {
            tipoVentaSeleccionado.value = tipoDefault;
            formVenta.tipoVenta = tipoDefault.value;
        }
    }
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeydown);
    // Limpiar timeout de búsqueda si existe
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

// Función para cerrar el modal de búsqueda en sucursales
const closeModal = () => {
    isModalOpen.value = false;
};

// Función para cerrar el modal de búsqueda de precio
const closePriceSearchModal = () => {
    isPriceSearchModalOpen.value = false;
    priceSearchQuery.value = '';
    priceSearchResults.value = [];
};

// Función para buscar precios de productos con debounce
let searchTimeout = null;
const searchProductPrice = async () => {
    if (bloqueoActivo.value) {
        return;
    }
    if (!priceSearchQuery.value.trim()) {
        priceSearchResults.value = [];
        return;
    }

    // Limpiar timeout anterior
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    // Usar debounce para evitar múltiples peticiones
    searchTimeout = setTimeout(async () => {
        isSearching.value = true;
        
        try {
            const response = await fetch(`/buscar-precio?q=${encodeURIComponent(priceSearchQuery.value)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            });

            if (response.ok) {
                const data = await response.json();
                priceSearchResults.value = data.productos || [];
            } else {
                console.error('Error en la búsqueda:', response.statusText);
                priceSearchResults.value = [];
                notify('error', 'Error al buscar productos');
            }
        } catch (error) {
            console.error('Error al buscar precios:', error);
            priceSearchResults.value = [];
            notify('error', 'Error de conexión al buscar productos');
        } finally {
            isSearching.value = false;
        }
    }, 300); // Esperar 300ms antes de hacer la búsqueda
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
            <br>
            <span class="dark:text-gray-400">F2: Buscar en sucursal</span>
            <span class="ml-4 dark:text-gray-400">F3: Buscar precio y existencias</span>
        </template>

        <hr class="my-6">

        <div v-if="mensajeBloqueoUI" class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-red-800">
                <p class="font-semibold">Ventas bloqueadas</p>
                <p class="mt-1 text-sm">{{ mensajeBloqueoUI }}</p>
            </div>
        </div>

        <div v-if="!bloqueoActivo" class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grow">
                <div class="shadow bg-white md:rounded-md p-4 md-col-span-2 mt-5 md:mt-0">
                    <div style=" text-align: left;">
                        <h6>Selecciona un cliente</h6>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label>Cliente: </label>
                            <vue-single-select id="singleTest" placeholder="Seleccione un cliente" v-model="form.cliente"
                                option-key="id" option-label="nombre" @input="changeClient($event)"
                                :class="[selectClient ? 'pointer-events-none' : '', 'w-full max-w-full']" :options="clientes">
                            </vue-single-select>
                            <label class="block mt-2 text-sm text-gray-700">Porcentaje de descuento: {{ form.porcentaje_descuento }}%</label>
                        </div>
                        <div >
                            <div>
                                <label>Productos: </label>
                                <vue-single-select placeholder="Seleccione un producto" v-model="form.producto"
                                    option-key="barcode" option-label="nombre" @input="handleSelectChange($event)"
                                    :options="productosFiltrados" class="w-full max-w-full" :disabled="clientSelected">
                                </vue-single-select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <hr class="my-6">

                        <div style="text-align: center;">
                            <h6>Ticket de venta</h6>
                        </div>
                        <br>
                            <div class="md:hidden grid grid-cols-1 gap-4 w-full mb-4">
                                <div v-for="producto in productoVenta" :key="producto.producto.id" class="rounded-lg boder p-4 bg-white shadow-lg">
                                    <div class="text-sm text-gray-500">Nombre del producto</div>
                                    <div class="font-semibold text-gray-900">{{ producto.producto.nombre }}</div>
                                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <div class="text-gray-500">Cantidad</div>
                                            <input type="number" min="1" step="0.01" v-model.number="producto.cantidad" @input="updateQuantity(producto)"
                                                class="w-full border rounded-md p-1" />
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
                                            <button @click="eliminarProducto(producto)"
                                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
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
                                        <td class="px-4 py-2"> {{ producto.producto.nombre }} </td>
                                        <td class="px-4 py-2">
                                            <input type="number" min="1" step="0.01" v-model.number="producto.cantidad" @input="updateQuantity(producto)"
                                                class="w-20 border rounded-md p-1" />
                                        </td>
                                        <td class="px-4 py-2"> {{ producto.precio_unitario }} </td>
                                        <td class="px-4 py-2"> {{ producto.importe }} </td>
                                        <td class="px-4 py-2">
                                            <div class="inline-flex rounded-md shadow-sm" role="group">
                                                <button @click="eliminarProducto(producto)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <br>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5 ml-auto">
                            <div>
                                <label class="block font-medium text-sm text-gray-700">Tipo de venta</label>
                                <vue-single-select v-model="tipoVentaSeleccionado" :options="tipoVentaOptions" option-key="value" placeholder="Selecione" class="w-full max-w-full" />
                            </div>
                            <div v-if="formVenta.tipoVenta === 'Credito'">
                                <label class="block font-medium text-sm text-gray-700">Abono a cuenta: </label>
                                <input type="number" step="0.01" v-model="form.abono"
                                    class="form-input rounded-md shadow-sm w-full max-w-full" />
                            </div>
                        </div>
                        <hr>
                        </hr>
                        <div class="w-full flex flex-col md:flex-row justify-between mb-4 mt-4 px-4 gap-4">
                            <button @click="finalizeSale()" :disabled="clientSelected"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                        hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                        focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                        dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Terminar venta
                            </button>
                            <label class="font-bold">Total final: $ {{ total }} </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="!bloqueoActivo && isModalOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 p-4 sm:p-0">
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow-lg w-full sm:w-lg overflow-y-auto max-h-full">
                <h2 class="text-xl font-semibold mb-4">Busqueda en sucursales</h2>

                <!-- Input de búsqueda -->
                <input v-model="b" type="text" placeholder="Buscar producto..."
                    @keyup.escape="closeModal"
                    class="w-full p-2 border rounded-md focus:ring focus:ring-blue-300" />

                <!-- Tabla de productos -->
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-200 text-sm sm:text-base">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-2 sm:p-6">Producto</th>
                                <th class="border p-2 sm:p-6">Marca</th>
                                <th class="border p-2 sm:p-6">Sucursal</th>
                                <th class="border p-2 sm:p-6">Cantidad en stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="producto in props.productosSucursal" :key="producto.id">
                                <td class="border p-2 sm:p-6">{{ producto?.nombre }}</td>
                                <td class="border p-2 sm:p-6">{{ producto?.marca }}</td>
                                <td class="border p-2 sm:p-6">{{ producto?.sucursal?.nombre }}</td>
                                <td class="border p-2 sm:p-6 text-center">{{ producto?.cantidad }}</td>
                            </tr>
                            <tr v-if="props.productosSucursal.length === 0">
                                <td colspan="4" class="border p-2 sm:p-6 text-center text-gray-500">
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
                        Cerrar (ESC)
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Búsqueda de Precio (F3) -->
        <div v-if="!bloqueoActivo && isPriceSearchModalOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 p-4 sm:p-0 z-50">
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow-lg w-full sm:w-4xl max-w-4xl overflow-y-auto max-h-full">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Buscar Precio y Stock</h2>

                <!-- Input de búsqueda -->
                <div class="mb-4">
                    <input 
                        id="price-search-input"
                        v-model="priceSearchQuery" 
                        @input="searchProductPrice"
                        @keyup.escape="closePriceSearchModal"
                        type="text" 
                        placeholder="Buscar por nombre o código de barras..."
                        class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg" 
                    />
                </div>

                <!-- Indicador de búsqueda -->
                <div v-if="isSearching" class="text-center py-4">
                    <div class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Buscando...
                    </div>
                </div>

                <!-- Resultados de búsqueda -->
                <div v-if="!isSearching && priceSearchResults.length > 0" class="max-h-96 overflow-y-auto">
                    <div class="grid gap-4">
                        <div 
                            v-for="producto in priceSearchResults" 
                            :key="producto.id"
                            class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <!-- Info del producto -->
                                <div class="flex-1">
                                    <h3 class="font-semibold text-lg text-gray-800">{{ producto.nombre_completo }}</h3>
                                    <div class="text-sm text-gray-600 mt-1">
                                        <span class="inline-block mr-4"><strong>Marca:</strong> {{ producto.marca }}</span>
                                        <span class="inline-block mr-4"><strong>Categoría:</strong> {{ producto.clasificacion }}</span>
                                        <span v-if="producto.barcode" class="inline-block"><strong>Código:</strong> {{ producto.barcode }}</span>
                                    </div>
                                </div>
                                
                                <!-- Precios y Stock -->
                                <div class="flex flex-col md:flex-row gap-3 md:gap-6 text-center">
                                    
                                    <!-- Precio IEPS -->
                                    <div class="bg-green-100 rounded-lg p-3 min-w-24">
                                        <p class="text-xs text-green-600 font-medium">PRECIO</p>
                                        <p class="text-lg font-bold text-green-800">${{ parseFloat(producto.precio_ieps).toFixed(2) }}</p>
                                    </div>
                                    
                                    <!-- Stock -->
                                    <div class="rounded-lg p-3 min-w-24" :class="producto.stock > 0 ? 'bg-orange-100' : 'bg-red-100'">
                                        <p class="text-xs font-medium" :class="producto.stock > 0 ? 'text-orange-600' : 'text-red-600'">STOCK</p>
                                        <p class="text-lg font-bold" :class="producto.stock > 0 ? 'text-orange-800' : 'text-red-800'">
                                            {{ producto.stock }}
                                            <span v-if="producto.stock <= 0" class="text-xs">🚫 Sin stock</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sin resultados -->
                <div v-if="!isSearching && priceSearchQuery && priceSearchResults.length === 0" class="text-center py-8">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        <p class="text-lg">No se encontraron productos</p>
                        <p class="text-sm">Intenta con otro término de búsqueda</p>
                    </div>
                </div>

                <!-- Estado inicial -->
                <div v-if="!priceSearchQuery && !isSearching" class="text-center py-8">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <p class="text-lg">Busca productos por nombre o código</p>
                        <p class="text-sm">Escribe en el campo de búsqueda para ver precios y stock</p>
                    </div>
                </div>

                <!-- Botones -->
                <div class="mt-6 flex justify-center">
                    <button @click="closePriceSearchModal"
                        class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors focus:ring-2 focus:ring-gray-500">
                        Cerrar (ESC)
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
