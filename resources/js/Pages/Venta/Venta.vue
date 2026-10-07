<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, onMounted, onUnmounted, reactive, computed, nextTick } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { notify } from '@/utils/notify';
import { useConnectivityStore } from '@/stores/connectivity';
import { offlineProductRepository } from '@/Offline/repositories/OfflineProductRepository';
import { offlineSaleRepository } from '@/Offline/repositories/OfflineSaleRepository';
import { getOrCreateDeviceId } from '@/Offline/services/device';
import { SaleApplicationService } from '@/Domain/Sales/SaleApplicationService';
import { printProvisionalTicket } from '@/Offline/services/provisionalTicket';

const productoVenta = reactive([]);

const page = usePage();
const connectivity = useConnectivityStore();
const localProducts = ref([]);
const isCompletingSale = ref(false);
const saleApplicationService = new SaleApplicationService(connectivity);

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
const mensajeBloqueo = computed(() => props.motivoBloqueo || 'Esta sección está bloqueada, Contacte a su administrador.');

const ventasDiaModalOpen = ref(false);
const ventasDiaLoading = ref(false);
const ventasDiaData = ref(null);
const ventasDiaError = ref(null);
const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const formatCurrency = (value) => currencyFormatter.format(Number(value ?? 0));
const formatNumber = (value, digits = 2) => Number(value ?? 0).toFixed(digits);
const formatQuantity = (value) => {
    const number = Number(value ?? 0);
    return Number.isInteger(number) ? String(number) : number.toFixed(2);
};
const displayStock = (value) => (value === null || value === undefined ? 'N/D' : formatNumber(value, 2));
const normalize = (value) => String(value ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

const abrirVentasDiaModal = async () => {
    ventasDiaModalOpen.value = true;
    await cargarVentasDia();
};

const cerrarVentasDiaModal = () => {
    ventasDiaModalOpen.value = false;
};

const cargarVentasDia = async () => {
    ventasDiaLoading.value = true;
    ventasDiaData.value = null;
    ventasDiaError.value = null;

    try {
        if (!connectivity.isUsableOnline) {
            ventasDiaData.value = await buildOfflineDailySales();
            return;
        }

        const params = {};
        const sucursalId = page.props?.sucursalActiva?.id;
        if (sucursalId) {
            params.id_sucursal = sucursalId;
        }
        const { data } = await axios.get(route('reporte.ventasDia', params), {
            headers: { Accept: 'application/json' },
        });
        ventasDiaData.value = data;
    } catch (error) {
        ventasDiaError.value = 'No se pudieron cargar las ventas del día.';
        notify('No se pudieron cargar las ventas del día.', 'error');
        ventasDiaData.value = { rows: [] };
    } finally {
        ventasDiaLoading.value = false;
    }
};

const isToday = value => {
    const date = new Date(value);
    const today = new Date();
    return date.getFullYear() === today.getFullYear()
        && date.getMonth() === today.getMonth()
        && date.getDate() === today.getDate();
};

const buildOfflineDailySales = async () => {
    const localSales = (await offlineSaleRepository.listLocalSales())
        .filter(sale => sale.operation && sale.status !== 'cancelled_local' && isToday(sale.occurredAt));
    const rows = localSales.flatMap(sale => {
        const payload = sale.operation?.payload ?? {};
        const items = payload.items?.length ? payload.items : sale.items;
        return items.map(item => ({
            venta_id: sale.localFolio,
            fecha_venta: new Date(sale.occurredAt).toLocaleString('es-MX'),
            tipo_venta: sale.saleType,
            producto: item.name ?? `Producto ${item.productId}`,
            cantidad: Number(item.quantity),
            precio_unitario: Number(item.unitPrice),
            total: Number(item.total),
            cliente: payload.customerName ?? 'Cliente público',
            stock_anterior: null,
            stock_nuevo: null,
            usuario: payload.sellerName ?? page.props.auth.user.name,
            local_status: sale.status,
        }));
    });

    return {
        sucursal: { id: page.props.sucursalActiva?.id, nombre: page.props.sucursalActiva?.nombre },
        fecha: new Date().toLocaleDateString('es-MX'),
        total: localSales.reduce((sum, sale) => sum + Number(sale.total), 0),
        rows,
        offlinePartial: true,
        offlineSalesCount: localSales.length,
    };
};

watch(bloqueoActivo, (value) => {
    if (value) {
        notify(mensajeBloqueo.value, 'error');
    }
}, { immediate: true });

// ---------------------------------------------------------------------------
// Catálogo
// ---------------------------------------------------------------------------

const productSource = computed(() => connectivity.isUsableOnline ? props.productos : localProducts.value);

const loadLocalProducts = async (query = '') => {
    try {
        const products = await offlineProductRepository.search(query);
        localProducts.value = products.map((product) => ({
            id: Number(product.serverId),
            nombre: product.name,
            tamano: product.size,
            barcode: product.barcode,
            precio_unitario: product.unitPrice,
            precio_ieps: product.price,
            cantidad: product.stock?.estimatedQuantity ?? 0,
            offlineStock: product.stock,
        }));
    } catch (error) {
        localProducts.value = [];
        notify('El catálogo local todavía no está disponible. Conéctate para sincronizarlo.', 'error');
    }
};

watch(() => connectivity.mode, (mode) => {
    if (mode !== 'online') loadLocalProducts();
});

const busqueda = ref('');
const categoriaActiva = ref('Todas');
const searchInput = ref(null);

const categorias = computed(() => {
    const nombres = new Set(productSource.value.map(p => p.clasificacion).filter(Boolean));
    return ['Todas', ...[...nombres].sort((a, b) => a.localeCompare(b, 'es'))];
});

watch(categorias, (lista) => {
    if (!lista.includes(categoriaActiva.value)) categoriaActiva.value = 'Todas';
});

const productosVisibles = computed(() => {
    const needle = normalize(busqueda.value);
    return productSource.value.filter((producto) => {
        if (categoriaActiva.value !== 'Todas' && producto.clasificacion !== categoriaActiva.value) return false;
        if (!needle) return true;
        return normalize(`${producto.nombre} ${producto.tamano ?? ''} ${producto.marca ?? ''}`).includes(needle)
            || normalize(producto.barcode).includes(needle);
    });
});

const nombreProducto = (producto) => producto.tamano ? `${producto.nombre} - ${producto.tamano}` : producto.nombre;

// ---------------------------------------------------------------------------
// Cliente y venta actual
// ---------------------------------------------------------------------------

const form = useForm({
    cliente: {},
    abono: 0
});

const mensajeBloqueoUI = computed(() => {
    if (bloqueoActivo.value) {
        return form.errors.bloqueo || mensajeBloqueo.value;
    }

    return form.errors.bloqueo || null;
});

const tipoVenta = ref('Contado');
const isReprintingTicket = ref(false);

const clienteActual = computed(() => form.cliente ?? {});
const descuentoCliente = computed(() => Number(clienteActual.value?.porcentaje_descuento ?? 0));
const esClientePublico = computed(() => !clienteActual.value?.id || clienteActual.value.id === props.clientePublicoDefault);

const precioConDescuento = (producto) => (Number(producto.precio_ieps)
    - ((Number(producto.precio_ieps) / 100) * descuentoCliente.value)).toFixed(2);

const recalcularLinea = (linea) => {
    linea.importe = (Number(linea.cantidad) * Number(linea.precio_unitario)).toFixed(2);
};

const lineaDe = (productoId) => productoVenta.find(p => p.producto.id === productoId);
const cantidadEnVenta = (productoId) => Number(lineaDe(productoId)?.cantidad ?? 0);
const existencia = (producto) => Number(producto.cantidad ?? 0);

const avisoSinExistencia = (producto) => {
    const stock = existencia(producto);
    notify(stock <= 0
        ? `${producto.nombre} está agotado en esta sucursal.`
        : `Solo hay ${formatQuantity(stock)} de ${producto.nombre} en existencia.`, 'error');
};

const agregarProducto = (producto, cantidad = 1) => {
    if (bloqueoActivo.value) return;
    if (existencia(producto) <= 0) {
        abrirBusquedaSucursales(producto.nombre);
        return;
    }
    const existente = lineaDe(producto.id);
    const nuevaCantidad = Number(existente?.cantidad ?? 0) + cantidad;
    if (nuevaCantidad > existencia(producto)) {
        avisoSinExistencia(producto);
        return;
    }
    if (existente) {
        existente.cantidad = nuevaCantidad;
        recalcularLinea(existente);
        return;
    }
    const linea = {
        producto: { ...producto, nombre: nombreProducto(producto) },
        cantidad: nuevaCantidad,
        precio_unitario: precioConDescuento(producto),
        importe: 0,
    };
    recalcularLinea(linea);
    productoVenta.push(linea);
};

const cambiarCantidad = (linea, delta) => {
    const nuevaCantidad = Number(linea.cantidad) + delta;
    if (nuevaCantidad < 1) return;
    if (nuevaCantidad > existencia(linea.producto)) {
        avisoSinExistencia(linea.producto);
        return;
    }
    linea.cantidad = nuevaCantidad;
    recalcularLinea(linea);
};

const updateQuantity = (linea) => {
    if (linea.cantidad === null || linea.cantidad === '') {
        linea.importe = 0;
        return;
    }
    if (linea.cantidad > existencia(linea.producto)) {
        avisoSinExistencia(linea.producto);
        linea.cantidad = existencia(linea.producto);
    }
    if (linea.cantidad < 0.01) {
        linea.cantidad = 1;
    }
    recalcularLinea(linea);
};

const eliminarProducto = (linea) => {
    productoVenta.splice(productoVenta.indexOf(linea), 1);
};

const vaciarVenta = () => {
    productoVenta.splice(0);
};

// Al cambiar de cliente, se recalculan los precios con su descuento.
watch(descuentoCliente, () => {
    productoVenta.forEach((linea) => {
        linea.precio_unitario = precioConDescuento(linea.producto);
        recalcularLinea(linea);
    });
});

watch(esClientePublico, (publico) => {
    if (publico) tipoVenta.value = 'Contado';
});

const subtotalVenta = computed(() => productoVenta.reduce((sum, linea) => sum + Number(linea.producto.precio_ieps) * Number(linea.cantidad || 0), 0));
const totalVenta = computed(() => Number(productoVenta.reduce((sum, linea) => sum + Number(linea.importe || 0), 0).toFixed(2)));
const descuentoVenta = computed(() => Math.max(0, subtotalVenta.value - totalVenta.value));
const unidadesVenta = computed(() => productoVenta.reduce((sum, linea) => sum + Number(linea.cantidad || 0), 0));
const resumenArticulos = computed(() => {
    if (productoVenta.length === 0) return 'Sin productos';
    const unidades = formatQuantity(unidadesVenta.value);
    return unidadesVenta.value === 1 ? '1 artículo' : `${unidades} artículos`;
});

const clientesModalOpen = ref(false);
const busquedaCliente = ref('');
const clientesFiltrados = computed(() => {
    const needle = normalize(busquedaCliente.value);
    return props.clientes.filter(c => !needle || normalize(c.nombre).includes(needle));
});

const abrirClientes = () => {
    busquedaCliente.value = '';
    clientesModalOpen.value = true;
};

const seleccionarCliente = (cliente) => {
    form.cliente = cliente;
    clientesModalOpen.value = false;
};

// Hoja inferior del carrito en pantallas chicas
const carritoAbierto = ref(false);

// ---------------------------------------------------------------------------
// Cobro
// ---------------------------------------------------------------------------

const pagoModalOpen = ref(false);
const efectivoRecibido = ref('');
const ventaCompletada = ref(null);

const montoIngresado = computed(() => {
    const valor = parseFloat(String(efectivoRecibido.value).replace(/[^0-9.]/g, ''));
    return Number.isFinite(valor) ? valor : null;
});
const esCredito = computed(() => tipoVenta.value === 'Credito');
const cambio = computed(() => montoIngresado.value === null ? null : montoIngresado.value - totalVenta.value);
const saldoCredito = computed(() => Math.max(0, totalVenta.value - Number(form.abono || 0)));

const montosRapidos = computed(() => {
    const total = totalVenta.value;
    if (esCredito.value) {
        return [
            { label: 'Sin abono', value: 0 },
            { label: '50%', value: Number((total / 2).toFixed(2)) },
            { label: 'Total', value: total },
        ];
    }
    const montos = [{ label: 'Exacto', value: total }];
    for (const paso of [100, 500, 1000]) {
        let monto = Math.ceil(total / paso) * paso;
        while (monto <= total || montos.some(m => m.value === monto)) monto += paso;
        montos.push({ label: formatCurrency(monto).replace('.00', ''), value: monto });
    }
    return montos;
});

const elegirMontoRapido = (monto) => {
    if (esCredito.value) {
        form.abono = monto;
    } else {
        efectivoRecibido.value = String(monto);
    }
};

const puedeConfirmar = computed(() => {
    if (productoVenta.length === 0 || isCompletingSale.value) return false;
    if (esCredito.value) {
        const abono = Number(form.abono || 0);
        return abono >= 0 && abono <= totalVenta.value;
    }
    return montoIngresado.value === null || montoIngresado.value >= totalVenta.value;
});

const abrirCobro = () => {
    if (bloqueoActivo.value || productoVenta.length === 0) return;
    if (!form.cliente?.id) {
        notify('Selecciona un cliente para continuar.', 'error');
        abrirClientes();
        return;
    }
    efectivoRecibido.value = '';
    form.abono = 0;
    pagoModalOpen.value = true;
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

const mostrarVentaCompletada = (detalle) => {
    ventaCompletada.value = {
        total: totalVenta.value,
        cambio: !esCredito.value && cambio.value !== null && cambio.value > 0 ? cambio.value : 0,
        credito: esCredito.value,
        saldo: esCredito.value ? saldoCredito.value : 0,
        ...detalle,
    };
    pagoModalOpen.value = false;
    carritoAbierto.value = false;
};

const reimprimirVentaCompletada = () => {
    const venta = ventaCompletada.value;
    if (!venta) return;
    if (venta.ticketUrl) {
        printTicketSilently(venta.ticketUrl);
    } else if (venta.provisional) {
        printProvisionalTicket(...venta.provisional);
    }
};

const nuevaVenta = () => {
    const local = ventaCompletada.value?.local;
    ventaCompletada.value = null;
    if (local) {
        productoVenta.splice(0);
        seleccionarClientePorDefecto();
        tipoVenta.value = 'Contado';
        busqueda.value = '';
        nextTick(() => searchInput.value?.focus());
        return;
    }
    // Recarga para traer las existencias actualizadas del servidor.
    location.replace('/venta');
};

const finalizeSale = async () => {
    if (bloqueoActivo.value || !puedeConfirmar.value) {
        return;
    }
    if (!page.props.offline?.salesEnabled) {
        if (!connectivity.isUsableOnline) notify('Las ventas offline no están habilitadas para esta instalación.', 'error');
        else submitLegacySale();
        return;
    }

    isCompletingSale.value = true;
    try {
        const deviceId = await getOrCreateDeviceId();
        const command = {
            saleId: crypto.randomUUID(),
            operationId: crypto.randomUUID(),
            branchId: String(page.props.sucursalActiva.id),
            deviceId,
            userId: String(page.props.auth.user.id),
            customerId: String(form.cliente.id),
            customerName: form.cliente.nombre,
            sellerName: page.props.auth.user.name,
            saleType: tipoVenta.value,
            total: Number(totalVenta.value),
            items: productoVenta.map(item => ({ productId: String(item.producto.id), name: item.producto.nombre, quantity: Number(item.cantidad), unitPrice: Number(item.precio_unitario), total: Number(item.importe) })),
            payments: [{ id: crypto.randomUUID(), method: 'cash', amount: tipoVenta.value === 'Contado' ? Number(totalVenta.value) : Number(form.abono ?? 0) }],
            occurredAt: new Date().toISOString(),
            ticket: {
                companyName: page.props.empresaConfig?.nombre ?? 'AgroSys',
                address: page.props.sucursalActiva?.direccion ?? page.props.empresaConfig?.direccion,
                phone: page.props.empresaConfig?.telefono,
                rfc: page.props.empresaConfig?.rfc,
                notice: page.props.empresaConfig?.aviso ?? 'Gracias por su compra',
                branchName: page.props.sucursalActiva?.nombre,
            },
        };
        const result = await saleApplicationService.complete(command);
        if (result.status === 'pending_sync') {
            printProvisionalTicket(command, result, command.ticket);
            mostrarVentaCompletada({
                folio: result.localFolio,
                local: true,
                provisional: [command, result, command.ticket],
            });
            await loadLocalProducts();
        } else {
            const url = route('venta.ticket.html', { venta: result.serverSaleId ?? result.serverFolio });
            mostrarVentaCompletada({ folio: result.serverFolio ?? result.serverSaleId, ticketUrl: url });
            printTicketSilently(url);
        }
    } catch (error) {
        const messages = {
            OFFLINE_SESSION_EXPIRED: 'La autorización offline venció. Las ventas pendientes siguen disponibles, pero no se puede crear otra venta.',
            OFFLINE_SESSION_MISMATCH: 'La sesión offline no corresponde a este usuario o sucursal.',
            OFFLINE_SALE_NOT_AUTHORIZED: 'Este usuario o dispositivo no tiene autorización para vender sin conexión.',
        };
        notify(messages[error.message] ?? error?.response?.data?.message ?? error.message ?? 'No se pudo completar la venta.', 'error');
    } finally {
        isCompletingSale.value = false;
    }
}

const submitLegacySale = () => {
    if (productoVenta.length === 0) return;
    isCompletingSale.value = true;
    form.post(route('venta.store',
        {
            'id_cliente': form.cliente.id,
            'total': totalVenta.value,
            'producto_venta': productoVenta,
            'tipo_venta': tipoVenta.value + "",
            'abono': form.abono
        }),
        {
            preserveState: true,
            onSuccess: (data) => {
                // Do not force ?size=80; let the backend use the sucursal preference (ticket_width_mm).
                const url = route('venta.ticket.html', { venta: data.props.venta.id });
                mostrarVentaCompletada({ folio: data.props.venta.id, ticketUrl: url });
                printTicketSilently(url);
            },
            onError: (errors) => {
                if (errors?.bloqueo) {
                    bloqueoManual.value = true;
                }
                pagoModalOpen.value = false;
                const primerError = Object.values(errors ?? {})[0];
                if (primerError) notify(primerError, 'error');
                console.error(errors);
            },
            onFinish: () => {
                isCompletingSale.value = false;
            },
        });
}

const reprintLastTicket = async () => {
    if (bloqueoActivo.value || isReprintingTicket.value) {
        return;
    }
    if (!connectivity.isUsableOnline) {
        notify('Reimprimir una venta central requiere conexión.', 'error');
        return;
    }

    isReprintingTicket.value = true;
    try {
        const response = await fetch(route('venta.ticket.last'), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const payload = await response.json().catch(() => null);

        if (!response.ok || !payload?.venta_id) {
            const message = payload?.message || 'No se encontró un ticket para reimprimir.';
            notify(message, 'error');
            return;
        }

        // Do not force ?size=80; let the backend use the sucursal preference (ticket_width_mm).
        const url = route('venta.ticket.html', { venta: payload.venta_id });
        printTicketSilently(url, () => {
            notify('Ticket enviado a impresión.', 'success');
        });
    } catch (error) {
        console.error('Error reimprimiendo ticket', error);
        notify('Ocurrió un error al intentar reimprimir el ticket.', 'error');
    } finally {
        isReprintingTicket.value = false;
    }
};

// Enter en el buscador: el lector de código de barras agrega el producto directo.
const onBuscarEnter = () => {
    const needle = normalize(busqueda.value);
    if (!needle) return;
    const porCodigo = productSource.value.find(p => p.barcode && normalize(p.barcode) === needle);
    const candidato = porCodigo ?? (productosVisibles.value.length === 1 ? productosVisibles.value[0] : null);
    if (candidato) {
        agregarProducto(candidato);
        busqueda.value = '';
    } else if (productosVisibles.value.length === 0) {
        abrirBusquedaSucursales(busqueda.value);
    }
};

// ---------------------------------------------------------------------------
// Existencias en otras sucursales
// ---------------------------------------------------------------------------

const isModalOpen = ref(false);
const b = ref('');
const buscandoSucursales = ref(false);
const sucursalSearchInput = ref(null);
let sucursalTimeout = null;

const buscarEnSucursales = (value) => {
    if (sucursalTimeout) clearTimeout(sucursalTimeout);
    if (!connectivity.isUsableOnline || normalize(value).length < 2) {
        buscandoSucursales.value = false;
        return;
    }
    buscandoSucursales.value = true;
    sucursalTimeout = setTimeout(() => {
        router.get(route('venta.index', { b: value }), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['productosSucursal'],
            onFinish: () => {
                buscandoSucursales.value = false;
            },
        });
    }, 300);
};

watch(b, buscarEnSucursales);

const abrirBusquedaSucursales = (texto = '') => {
    if (bloqueoActivo.value) return;
    isModalOpen.value = true;
    if (b.value === texto) {
        buscarEnSucursales(texto);
    } else {
        b.value = texto;
    }
    nextTick(() => sucursalSearchInput.value?.focus());
};

const closeModal = () => {
    isModalOpen.value = false;
};

const resultadosSucursales = computed(() => {
    const grupos = new Map();
    for (const fila of props.productosSucursal ?? []) {
        if (!grupos.has(fila.id)) {
            const local = productSource.value.find(p => p.id === fila.id);
            grupos.set(fila.id, {
                id: fila.id,
                nombre: fila.nombre,
                tamano: fila.tamano,
                marca: fila.marca,
                precio: fila.precio_ieps,
                existenciaLocal: local ? existencia(local) : 0,
                sucursales: [],
            });
        }
        grupos.get(fila.id).sucursales.push({ id: fila.sucursal?.id, nombre: fila.sucursal?.nombre, cantidad: Number(fila.cantidad) });
    }
    return [...grupos.values()].map(grupo => ({
        ...grupo,
        sucursales: grupo.sucursales.sort((x, y) => y.cantidad - x.cantidad),
        totalOtras: grupo.sucursales.reduce((sum, s) => sum + s.cantidad, 0),
    }));
});

const estiloExistencia = (cantidad) => {
    if (cantidad <= 0) return 'bg-gray-100 text-gray-600';
    if (cantidad <= 5) return 'bg-amber-100 text-amber-800';
    return 'bg-green-100 text-green-800';
};

// ---------------------------------------------------------------------------
// Teclado y arranque
// ---------------------------------------------------------------------------

const cerrarModalActivo = () => {
    if (pagoModalOpen.value) pagoModalOpen.value = false;
    else if (clientesModalOpen.value) clientesModalOpen.value = false;
    else if (isModalOpen.value) closeModal();
    else if (carritoAbierto.value) carritoAbierto.value = false;
};

const handleKeydown = (event) => {
    if (bloqueoActivo.value) {
        return;
    }
    if (event.key === "F2") {
        event.preventDefault();
        abrirBusquedaSucursales();
    } else if (event.key === "F3") {
        event.preventDefault();
        searchInput.value?.focus();
        searchInput.value?.select();
    } else if (event.key === "F4") {
        event.preventDefault();
        abrirCobro();
    } else if (event.key === "Escape") {
        cerrarModalActivo();
    }
};

const seleccionarClientePorDefecto = () => {
    if (props.clientePublicoDefault && props.clientes.length > 0) {
        const clientePublico = props.clientes.find(c => c.id === props.clientePublicoDefault);
        if (clientePublico) {
            form.cliente = clientePublico;
            return;
        }
    }
    if (props.clientes.length > 0) {
        form.cliente = props.clientes[0];
    }
};

onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
    if (!connectivity.isUsableOnline) loadLocalProducts();

    seleccionarClientePorDefecto();

    if (props.tipoVentaDefault) {
        tipoVenta.value = props.tipoVentaDefault.toLowerCase() === 'credito' && !esClientePublico.value ? 'Credito' : 'Contado';
    }
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeydown);
    if (sucursalTimeout) {
        clearTimeout(sucursalTimeout);
    }
});
</script>

<template>
    <AppLayout title="Punto de venta">
        <div
            class="flex flex-col overflow-hidden bg-gray-100 dark:bg-gray-900"
            :class="connectivity.mode !== 'online' ? 'h-[calc(100dvh-7rem)]' : 'h-[calc(100dvh-4rem)]'"
        >
            <!-- Barra de la caja -->
            <div class="flex shrink-0 flex-wrap items-center gap-x-3 gap-y-2 border-b border-gray-200 bg-white px-4 py-2.5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                <div class="order-3 flex w-full min-w-0 items-center justify-between gap-3 border-t border-gray-100 pt-2 dark:border-gray-700 sm:order-none sm:w-auto sm:flex-1 sm:border-0 sm:pt-0">
                    <div class="min-w-0">
                        <h1 class="hidden text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100 sm:block">Punto de venta</h1>
                        <p class="truncate text-sm text-gray-600 dark:text-gray-400">
                            <span class="sm:hidden">Sucursal </span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100 sm:font-normal sm:text-gray-600 sm:dark:text-gray-400">{{ page.props.sucursalActiva?.nombre ?? 'Sucursal activa' }}</span>
                        </p>
                    </div>
                    <span v-if="connectivity.mode === 'online'" class="inline-flex h-8 shrink-0 items-center gap-2 rounded-full bg-green-100 px-3 text-xs font-bold text-green-800">
                        <span class="h-2 w-2 rounded-full bg-green-600" aria-hidden="true"></span>En línea
                    </span>
                    <span v-else class="inline-flex h-8 shrink-0 items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 text-xs font-bold text-amber-900">
                        <span class="h-2 w-2 rounded-full bg-amber-600" aria-hidden="true"></span>Sin conexión
                    </span>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" @click="abrirBusquedaSucursales()" :disabled="bloqueoActivo"
                        class="inline-flex h-11 min-w-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        aria-label="Buscar en otras sucursales (F2)" title="Buscar en otras sucursales (F2)">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" /></svg>
                        <span class="hidden lg:inline">Otras sucursales</span>
                    </button>
                    <button type="button" @click="abrirVentasDiaModal"
                        class="inline-flex h-11 min-w-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        aria-label="Ventas del día" title="Ventas del día">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        <span class="hidden lg:inline">Ventas del día</span>
                    </button>
                    <button type="button" @click="reprintLastTicket" :disabled="isReprintingTicket || bloqueoActivo"
                        class="inline-flex h-11 min-w-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        aria-label="Reimprimir último ticket" title="Reimprimir último ticket">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4" /></svg>
                        <span class="hidden lg:inline">{{ isReprintingTicket ? 'Reimprimiendo…' : 'Reimprimir' }}</span>
                    </button>
                </div>
            </div>

            <!-- Ventas bloqueadas -->
            <div v-if="mensajeBloqueoUI" class="shrink-0 px-4 pt-4 sm:px-6">
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
                    <p class="font-semibold">Ventas bloqueadas</p>
                    <p class="mt-1 text-sm">{{ mensajeBloqueoUI }}</p>
                </div>
            </div>

            <div v-if="!bloqueoActivo" class="grid min-h-0 flex-1 lg:grid-cols-[minmax(0,1fr)_400px]">

                <!-- Catálogo -->
                <main class="flex min-h-0 flex-col gap-4 overflow-y-auto px-4 pb-28 pt-4 sm:px-6 lg:pb-8 [&>*]:shrink-0">
                    <div v-if="connectivity.mode !== 'online'" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        <p><strong>Catálogo local.</strong> Puedes seguir vendiendo con los productos guardados en este dispositivo; las existencias son estimadas.</p>
                    </div>

                    <label class="relative block">
                        <span class="sr-only">Buscar producto o escanear código</span>
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                        <input ref="searchInput" v-model="busqueda" type="search" autocomplete="off" enterkeyhint="search"
                            @keydown.enter.prevent="onBuscarEnter"
                            placeholder="Buscar producto o escanear código"
                            class="block h-14 w-full rounded-xl border-2 border-gray-300 bg-white pl-12 pr-4 text-[17px] text-gray-900 placeholder:text-gray-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                    </label>

                    <div v-if="categorias.length > 2" class="-mx-1 flex gap-2 overflow-x-auto px-1 py-0.5 [scrollbar-width:none]" role="tablist" aria-label="Clasificación">
                        <button v-for="categoria in categorias" :key="categoria" type="button" role="tab"
                            :aria-selected="categoriaActiva === categoria"
                            @click="categoriaActiva = categoria"
                            class="h-11 shrink-0 rounded-full border px-4 text-[15px] font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                            :class="categoriaActiva === categoria
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200'">
                            {{ categoria }}
                        </button>
                    </div>

                    <div class="flex items-baseline justify-between gap-3 text-sm text-gray-600 dark:text-gray-400">
                        <span>{{ productosVisibles.length === 1 ? '1 producto' : `${productosVisibles.length} productos` }}</span>
                        <span class="hidden sm:inline">Toca un producto para agregarlo · F3 buscar · F4 cobrar</span>
                    </div>

                    <div class="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-2.5 sm:grid-cols-[repeat(auto-fill,minmax(176px,1fr))] sm:gap-3">
                        <button v-for="producto in productosVisibles" :key="producto.id" type="button"
                            @click="agregarProducto(producto)"
                            :aria-label="existencia(producto) > 0 ? `Agregar ${nombreProducto(producto)}, ${formatCurrency(producto.precio_ieps)}` : `${nombreProducto(producto)} agotado. Ver otras sucursales`"
                            class="relative flex min-h-[136px] flex-col gap-1.5 rounded-xl border-2 p-3 text-left text-gray-900 transition active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:text-gray-100 sm:min-h-[148px] sm:p-3.5"
                            :class="existencia(producto) <= 0
                                ? 'border-dashed border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-800'
                                : cantidadEnVenta(producto.id) > 0
                                    ? 'border-blue-600 bg-blue-50 dark:border-blue-400 dark:bg-blue-950'
                                    : 'border-gray-200 bg-white hover:shadow-md dark:border-gray-700 dark:bg-gray-800'">
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ producto.clasificacion ?? producto.marca ?? '' }}</span>
                                <span v-if="cantidadEnVenta(producto.id) > 0" class="inline-flex h-7 min-w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 px-2 text-sm font-bold text-white">
                                    {{ formatQuantity(cantidadEnVenta(producto.id)) }}
                                </span>
                            </span>
                            <span class="text-[17px] font-bold leading-snug">{{ producto.nombre }}</span>
                            <span v-if="producto.tamano" class="text-sm text-gray-600 dark:text-gray-400">{{ producto.tamano }}</span>
                            <span class="flex-1"></span>
                            <span v-if="existencia(producto) <= 0" class="inline-flex items-center gap-1.5 text-[13px] font-bold text-blue-700 dark:text-blue-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                                Ver en otras sucursales
                            </span>
                            <span class="flex items-end justify-between gap-2">
                                <span class="text-xl font-extrabold">{{ formatCurrency(producto.precio_ieps) }}</span>
                                <span class="whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-bold"
                                    :class="existencia(producto) <= 0 ? 'bg-gray-200 text-gray-700' : existencia(producto) <= 5 ? 'bg-amber-100 text-amber-800' : 'bg-green-50 text-green-800'">
                                    {{ existencia(producto) <= 0 ? 'Agotado aquí' : existencia(producto) <= 5 ? `Quedan ${formatQuantity(existencia(producto))}` : `${formatQuantity(existencia(producto))} en exist.` }}
                                </span>
                            </span>
                        </button>
                    </div>

                    <div v-if="productosVisibles.length === 0" class="px-4 py-12 text-center text-gray-600 dark:text-gray-400">
                        <p class="text-[17px] font-bold text-gray-900 dark:text-gray-100">
                            {{ busqueda ? `No encontramos “${busqueda}” en esta sucursal` : 'No hay productos con existencia' }}
                        </p>
                        <p class="mt-1.5 text-[15px]">Revisa la escritura o búscalo en otras sucursales.</p>
                        <button type="button" @click="abrirBusquedaSucursales(busqueda)"
                            class="mt-4 inline-flex h-12 items-center gap-2 rounded-lg border border-blue-300 bg-white px-5 text-[15px] font-semibold text-blue-700 hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:bg-gray-800 dark:text-blue-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" /></svg>
                            Buscar en otras sucursales
                        </button>
                    </div>
                </main>

                <!-- Fondo de la hoja en pantallas chicas -->
                <div v-if="carritoAbierto" class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @click="carritoAbierto = false" aria-hidden="true"></div>

                <!-- Venta actual -->
                <aside aria-label="Venta actual"
                    class="fixed inset-x-0 bottom-0 z-40 flex max-h-[90dvh] flex-col rounded-t-2xl bg-white shadow-2xl transition-transform duration-300 dark:bg-gray-800 lg:static lg:z-auto lg:max-h-none lg:min-h-0 lg:translate-y-0 lg:rounded-none lg:border-l lg:border-gray-200 lg:shadow-none lg:dark:border-gray-700"
                    :class="carritoAbierto ? 'translate-y-0' : 'translate-y-full'">
                    <div class="flex items-center gap-2 px-5 pb-3 pt-4">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Venta actual</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ resumenArticulos }}</p>
                        </div>
                        <button v-if="productoVenta.length" type="button" @click="vaciarVenta"
                            class="h-11 rounded-lg border border-red-200 px-3 text-sm font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:border-red-500 dark:text-red-300 dark:hover:bg-red-900">
                            Vaciar
                        </button>
                        <button type="button" @click="carritoAbierto = false" aria-label="Cerrar venta"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 lg:hidden">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-5 pb-3">
                        <button type="button" @click="abrirClientes"
                            class="flex min-h-[60px] w-full items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-left text-gray-900 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 11a3 3 0 100-6 3 3 0 000 6zM6 18a4 4 0 014-4h4a4 4 0 014 4" /></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs text-gray-600 dark:text-gray-400">Cliente</span>
                                <span class="block truncate font-semibold">{{ clienteActual.nombre ?? 'Selecciona un cliente' }}</span>
                            </span>
                            <span v-if="descuentoCliente > 0" class="shrink-0 rounded-md bg-green-100 px-2 py-0.5 text-[13px] font-bold text-green-800">−{{ descuentoCliente }}%</span>
                            <span class="shrink-0 text-sm font-semibold text-blue-700 dark:text-blue-400">Cambiar</span>
                        </button>
                    </div>

                    <div class="min-h-[120px] flex-1 overflow-y-auto border-t border-gray-200 dark:border-gray-700">
                        <div v-if="productoVenta.length === 0" class="flex flex-col items-center gap-2.5 px-6 py-10 text-center text-gray-600 dark:text-gray-400">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-700">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </span>
                            <p class="font-bold text-gray-900 dark:text-gray-100">Aún no hay productos</p>
                            <p class="text-sm">Toca un producto del catálogo o escanea su código de barras.</p>
                        </div>

                        <div v-for="linea in productoVenta" :key="linea.producto.id" class="flex flex-col gap-2.5 border-b border-gray-100 px-5 py-3.5 dark:border-gray-700">
                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold leading-5 text-gray-900 dark:text-gray-100">{{ linea.producto.nombre }}</p>
                                    <p class="text-[13px] text-gray-600 dark:text-gray-400">{{ formatCurrency(linea.precio_unitario) }} c/u</p>
                                </div>
                                <p class="whitespace-nowrap text-[17px] font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(linea.importe) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="cambiarCantidad(linea, -1)" :disabled="Number(linea.cantidad) <= 1" aria-label="Quitar uno"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-900 disabled:border-gray-200 disabled:text-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14" /></svg>
                                </button>
                                <label class="sr-only" :for="`cantidad-${linea.producto.id}`">Cantidad de {{ linea.producto.nombre }}</label>
                                <input :id="`cantidad-${linea.producto.id}`" v-model.number="linea.cantidad" @change="updateQuantity(linea)"
                                    type="number" inputmode="decimal" min="0.01" step="0.01"
                                    class="h-11 w-16 rounded-lg border-gray-300 text-center text-lg font-bold text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" />
                                <button type="button" @click="cambiarCantidad(linea, 1)" :disabled="Number(linea.cantidad) + 1 > existencia(linea.producto)" aria-label="Agregar uno"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-900 disabled:border-gray-200 disabled:text-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                                </button>
                                <span v-if="Number(linea.cantidad) + 1 > existencia(linea.producto)" class="text-xs font-semibold text-amber-800 dark:text-amber-300">Máx. en existencia</span>
                                <span class="flex-1"></span>
                                <button type="button" @click="eliminarProducto(linea)" :aria-label="`Eliminar ${linea.producto.nombre}`"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-red-700 dark:hover:bg-gray-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3.5 border-t border-gray-200 px-5 pb-5 pt-4 dark:border-gray-700">
                        <div role="radiogroup" aria-label="Tipo de venta" class="flex gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-900">
                            <button type="button" role="radio" :aria-checked="tipoVenta === 'Contado'" @click="tipoVenta = 'Contado'"
                                class="h-12 flex-1 rounded-lg text-base font-semibold transition"
                                :class="tipoVenta === 'Contado' ? 'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-white' : 'text-gray-700 dark:text-gray-300'">
                                Contado
                            </button>
                            <button type="button" role="radio" :aria-checked="tipoVenta === 'Credito'" @click="tipoVenta = 'Credito'" :disabled="esClientePublico"
                                class="h-12 flex-1 rounded-lg text-base font-semibold transition disabled:text-gray-400"
                                :class="tipoVenta === 'Credito' ? 'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-white' : 'text-gray-700 dark:text-gray-300'">
                                Crédito
                            </button>
                        </div>
                        <p v-if="esClientePublico" class="-mt-1.5 text-[13px] text-gray-600 dark:text-gray-400">Para vender a crédito, elige un cliente registrado.</p>

                        <div class="flex flex-col gap-1.5 text-[15px]">
                            <div class="flex justify-between text-gray-600 dark:text-gray-400"><span>Subtotal</span><span>{{ formatCurrency(subtotalVenta) }}</span></div>
                            <div v-if="descuentoVenta > 0.004" class="flex justify-between text-green-800 dark:text-green-400"><span>Descuento cliente ({{ descuentoCliente }}%)</span><span>−{{ formatCurrency(descuentoVenta) }}</span></div>
                        </div>

                        <button type="button" @click="abrirCobro" :disabled="productoVenta.length === 0"
                            class="flex h-16 w-full items-center justify-between gap-3 rounded-xl bg-blue-600 px-5 text-lg font-bold text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:bg-gray-300 disabled:text-gray-600">
                            <span>Cobrar</span>
                            <span class="text-2xl font-extrabold">{{ formatCurrency(totalVenta) }}</span>
                        </button>
                    </div>
                </aside>
            </div>

            <!-- Barra inferior en pantallas chicas -->
            <div v-if="!bloqueoActivo" class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white px-4 pb-4 pt-3 dark:border-gray-700 dark:bg-gray-800 lg:hidden">
                <button type="button" @click="carritoAbierto = true"
                    class="flex h-[60px] w-full items-center justify-between gap-3 rounded-xl bg-blue-600 px-5 font-bold text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                    <span class="inline-flex items-center gap-2.5">
                        <span class="inline-flex h-[30px] min-w-[30px] items-center justify-center rounded-full bg-white px-2 text-[15px] font-extrabold text-blue-700">{{ formatQuantity(unidadesVenta) }}</span>
                        <span class="text-[17px]">Ver venta</span>
                    </span>
                    <span class="text-xl font-extrabold">{{ formatCurrency(totalVenta) }}</span>
                </button>
            </div>
        </div>

        <!-- Elegir cliente -->
        <div v-if="clientesModalOpen" class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/55 sm:items-center sm:p-6" @click.self="clientesModalOpen = false">
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-clientes" class="flex max-h-[90dvh] w-full flex-col gap-3 rounded-t-2xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:max-w-lg sm:rounded-2xl">
                <div class="flex items-center justify-between">
                    <h2 id="titulo-clientes" class="text-xl font-bold text-gray-900 dark:text-gray-100">Elegir cliente</h2>
                    <button type="button" @click="clientesModalOpen = false" aria-label="Cerrar" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <label class="block">
                    <span class="sr-only">Buscar cliente</span>
                    <input v-model="busquedaCliente" type="search" autocomplete="off" placeholder="Buscar cliente"
                        class="h-12 w-full rounded-xl border-2 border-gray-300 px-4 text-base focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                </label>
                <div class="-mx-1 flex min-h-0 flex-col gap-2 overflow-y-auto px-1 pb-1">
                    <button v-for="cliente in clientesFiltrados" :key="cliente.id" type="button" @click="seleccionarCliente(cliente)"
                        class="flex min-h-[60px] w-full items-center gap-3 rounded-xl border-2 px-3.5 py-2 text-left text-gray-900 dark:text-gray-100"
                        :class="cliente.id === clienteActual.id ? 'border-blue-600 bg-blue-50 dark:bg-blue-950' : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800'">
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ cliente.nombre }}</span>
                            <span class="block text-[13px] text-gray-600 dark:text-gray-400">
                                {{ cliente.id === clientePublicoDefault ? 'Solo contado' : (Number(cliente.balance ?? 0) > 0 ? `Saldo pendiente ${formatCurrency(cliente.balance)}` : 'Sin adeudo') }}
                            </span>
                        </span>
                        <span v-if="Number(cliente.porcentaje_descuento) > 0" class="shrink-0 rounded-md bg-green-100 px-2 py-0.5 text-[13px] font-bold text-green-800">−{{ cliente.porcentaje_descuento }}%</span>
                    </button>
                    <p v-if="clientesFiltrados.length === 0" class="py-6 text-center text-gray-600">No hay clientes con ese nombre.</p>
                </div>
            </div>
        </div>

        <!-- Cobro -->
        <div v-if="pagoModalOpen" class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/55 sm:items-center sm:p-6" @click.self="pagoModalOpen = false">
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-cobro" class="flex max-h-[95dvh] w-full flex-col gap-4 overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:max-w-lg sm:rounded-2xl">
                <div class="flex items-center justify-between">
                    <h2 id="titulo-cobro" class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ esCredito ? 'Venta a crédito' : 'Cobro en efectivo' }}</h2>
                    <button type="button" @click="pagoModalOpen = false" aria-label="Volver a la venta" class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total a pagar · {{ clienteActual.nombre }}</p>
                    <p class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ formatCurrency(totalVenta) }}</p>
                </div>

                <label v-if="!esCredito" class="flex flex-col gap-1.5">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Efectivo recibido (opcional)</span>
                    <input v-model="efectivoRecibido" type="text" inputmode="decimal" autocomplete="off" placeholder="$0.00"
                        class="h-[60px] rounded-xl border-2 border-gray-300 px-4 text-right text-2xl font-bold text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                </label>
                <label v-else class="flex flex-col gap-1.5">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Abono inicial (opcional)</span>
                    <input v-model.number="form.abono" type="number" inputmode="decimal" min="0" step="0.01" placeholder="$0.00"
                        class="h-[60px] rounded-xl border-2 border-gray-300 px-4 text-right text-2xl font-bold text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                </label>

                <div class="grid gap-2" :class="montosRapidos.length === 4 ? 'grid-cols-4' : 'grid-cols-3'">
                    <button v-for="monto in montosRapidos" :key="monto.label" type="button" @click="elegirMontoRapido(monto.value)"
                        class="h-[52px] rounded-lg border text-base font-semibold"
                        :class="(esCredito ? Number(form.abono || 0) === monto.value : montoIngresado === monto.value)
                            ? 'border-blue-600 bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                            : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100'">
                        {{ monto.label }}
                    </button>
                </div>

                <div v-if="esCredito" class="flex items-center justify-between rounded-xl bg-blue-50 px-4 py-3.5 text-blue-900 dark:bg-blue-950 dark:text-blue-200">
                    <span class="font-semibold">Saldo a crédito</span>
                    <span class="text-2xl font-extrabold">{{ formatCurrency(saldoCredito) }}</span>
                </div>
                <div v-else-if="cambio !== null" class="flex items-center justify-between rounded-xl px-4 py-3.5"
                    :class="cambio >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-50 text-red-800'">
                    <span class="font-semibold">{{ cambio >= 0 ? 'Cambio' : 'Falta' }}</span>
                    <span class="text-2xl font-extrabold">{{ formatCurrency(Math.abs(cambio)) }}</span>
                </div>
                <p v-if="esCredito && Number(form.abono || 0) > totalVenta" class="-mt-2 text-sm font-semibold text-red-700">El abono no puede ser mayor al total.</p>

                <button type="button" @click="finalizeSale" :disabled="!puedeConfirmar"
                    class="flex h-16 w-full items-center justify-center gap-2 rounded-xl bg-green-700 text-lg font-bold text-white hover:bg-green-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:bg-gray-300 disabled:text-gray-600">
                    <svg v-if="!isCompletingSale" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
                    {{ isCompletingSale ? 'Guardando venta…' : 'Confirmar venta' }}
                </button>
            </div>
        </div>

        <!-- Venta registrada -->
        <div v-if="ventaCompletada" class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/55 sm:items-center sm:p-6">
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-completada" class="flex w-full flex-col items-center gap-3.5 rounded-t-2xl bg-white px-5 pb-5 pt-7 text-center shadow-2xl dark:bg-gray-800 sm:max-w-lg sm:rounded-2xl">
                <span class="inline-flex h-[72px] w-[72px] items-center justify-center rounded-full bg-green-100 text-green-800">
                    <svg class="h-9 w-9" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
                </span>
                <h2 id="titulo-completada" class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">
                    {{ ventaCompletada.local ? 'Venta guardada en este dispositivo' : 'Venta registrada' }}
                </h2>
                <p class="text-[15px] text-gray-600 dark:text-gray-400">
                    Folio {{ ventaCompletada.folio }} · {{ formatCurrency(ventaCompletada.total) }}{{ ventaCompletada.credito ? ` · saldo a crédito ${formatCurrency(ventaCompletada.saldo)}` : '' }}
                    <span v-if="ventaCompletada.local" class="block">Se sincronizará cuando vuelva la conexión.</span>
                </p>
                <div v-if="ventaCompletada.cambio > 0" class="w-full rounded-xl bg-blue-50 p-4 text-blue-900 dark:bg-blue-950 dark:text-blue-200">
                    <p class="text-sm font-semibold">Cambio a entregar</p>
                    <p class="text-4xl font-extrabold">{{ formatCurrency(ventaCompletada.cambio) }}</p>
                </div>
                <div class="grid w-full grid-cols-2 gap-2.5">
                    <button type="button" @click="reimprimirVentaCompletada"
                        class="inline-flex h-[60px] items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white text-base font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4" /></svg>
                        Imprimir ticket
                    </button>
                    <button type="button" @click="nuevaVenta"
                        class="h-[60px] rounded-xl bg-blue-600 text-[17px] font-bold text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                        Nueva venta
                    </button>
                </div>
            </div>
        </div>

        <!-- Existencias en otras sucursales (F2) -->
        <div v-if="!bloqueoActivo && isModalOpen" class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/55 sm:items-center sm:p-6" @click.self="closeModal">
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-sucursales" class="flex max-h-[90dvh] w-full flex-col gap-3.5 rounded-t-2xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:max-w-xl sm:rounded-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="titulo-sucursales" class="text-xl font-bold text-gray-900 dark:text-gray-100">Existencias en otras sucursales</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Consulta antes de prometer un producto al cliente.</p>
                    </div>
                    <button type="button" @click="closeModal" aria-label="Cerrar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div v-if="!connectivity.isUsableOnline" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    <p><strong>Necesitas conexión.</strong> Las existencias de otras sucursales se consultan en el servidor; vuelve a intentarlo cuando regrese la conexión.</p>
                </div>

                <template v-else>
                    <label class="relative block">
                        <span class="sr-only">Producto a buscar</span>
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
                        <input ref="sucursalSearchInput" v-model="b" type="search" autocomplete="off" placeholder="Nombre o código del producto"
                            class="h-[52px] w-full rounded-xl border-2 border-gray-300 pl-12 pr-4 text-[17px] text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </label>

                    <div class="-mx-1 flex min-h-0 flex-col gap-3 overflow-y-auto px-1 pb-1">
                        <p v-if="b.trim().length < 2" class="py-7 text-center text-[15px] text-gray-600 dark:text-gray-400">Escribe al menos 2 letras del producto.</p>
                        <p v-else-if="buscandoSucursales" class="py-7 text-center text-[15px] text-gray-600 dark:text-gray-400">Buscando en las sucursales…</p>
                        <p v-else-if="resultadosSucursales.length === 0" class="py-7 text-center text-[15px] text-gray-600 dark:text-gray-400">Ninguna otra sucursal tiene “{{ b }}” en existencia.</p>

                        <template v-else>
                            <section v-for="resultado in resultadosSucursales" :key="resultado.id" class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                                <div class="flex items-baseline justify-between gap-3 px-3.5 py-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-900 dark:text-gray-100">{{ resultado.nombre }}</p>
                                        <p class="text-[13px] text-gray-600 dark:text-gray-400">{{ [resultado.marca, resultado.tamano].filter(Boolean).join(' · ') }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(resultado.precio) }}</p>
                                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ formatQuantity(resultado.totalOtras) }} en otras</p>
                                    </div>
                                </div>
                                <div class="flex min-h-[52px] items-center gap-3 border-t border-gray-100 bg-gray-50 px-3.5 py-2 dark:border-gray-700 dark:bg-gray-900">
                                    <span class="min-w-0 flex-1 font-semibold text-gray-900 dark:text-gray-100">{{ page.props.sucursalActiva?.nombre ?? 'Esta sucursal' }}</span>
                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Esta sucursal</span>
                                    <span class="min-w-[88px] rounded-md px-2.5 py-1 text-center text-[13px] font-bold" :class="estiloExistencia(resultado.existenciaLocal)">
                                        {{ resultado.existenciaLocal > 0 ? `${formatQuantity(resultado.existenciaLocal)} pzas` : 'Agotado' }}
                                    </span>
                                </div>
                                <div v-for="sucursal in resultado.sucursales" :key="sucursal.id" class="flex min-h-[52px] items-center gap-3 border-t border-gray-100 px-3.5 py-2 dark:border-gray-700">
                                    <svg class="h-[18px] w-[18px] shrink-0 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" /></svg>
                                    <span class="min-w-0 flex-1 font-semibold text-gray-900 dark:text-gray-100">{{ sucursal.nombre }}</span>
                                    <span class="min-w-[88px] rounded-md px-2.5 py-1 text-center text-[13px] font-bold" :class="estiloExistencia(sucursal.cantidad)">
                                        {{ formatQuantity(sucursal.cantidad) }} pzas
                                    </span>
                                </div>
                            </section>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <DialogModal :show="ventasDiaModalOpen" maxWidth="6xl" @close="cerrarVentasDiaModal">
            <template #title>
                Ventas del día
            </template>
            <template #content>
                <div v-if="ventasDiaLoading" class="text-sm text-gray-600 dark:text-gray-300">
                    Cargando ventas de hoy...
                </div>
                <div v-else>
                    <div class="flex flex-col gap-1 text-sm text-gray-600 dark:text-gray-300">
                        <span><strong class="text-gray-800 dark:text-gray-100">Sucursal:</strong> {{ ventasDiaData?.sucursal?.nombre ?? 'Sucursal activa' }}</span>
                        <span><strong class="text-gray-800 dark:text-gray-100">Fecha:</strong> {{ ventasDiaData?.fecha ?? 'Hoy' }}</span>
                    </div>

                    <div v-if="ventasDiaData?.offlinePartial" class="mt-4 rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="status">
                        <p class="font-semibold">Mostrando únicamente las ventas guardadas en este dispositivo.</p>
                        <p class="mt-1">Las demás ventas realizadas hoy en la sucursal aparecerán cuando Agrosys vuelva a estar en línea.</p>
                    </div>

                    <div v-if="ventasDiaError" class="mt-3 text-sm text-red-600 dark:text-red-300">
                        {{ ventasDiaError }}
                    </div>

                    <div v-if="!ventasDiaData || !ventasDiaData.rows || ventasDiaData.rows.length === 0" class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                        {{ ventasDiaData?.offlinePartial ? 'No hay ventas offline registradas hoy en este dispositivo.' : 'No hay ventas registradas para la fecha actual.' }}
                    </div>
                    <div v-else class="mt-4 space-y-3 md:space-y-0">
                        <div class="grid gap-3 md:hidden">
                            <div v-for="(row, index) in ventasDiaData.rows" :key="index" class="rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Venta #{{ row.venta_id }}</div>
                                    <span class="text-[11px] uppercase text-gray-500 dark:text-gray-400">{{ row.tipo_venta }}</span>
                                </div>
                                <span v-if="row.local_status" class="mt-2 inline-flex rounded bg-amber-100 px-2 py-1 text-[10px] font-semibold uppercase text-amber-800">{{ row.local_status }}</span>
                                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ row.fecha_venta }}</div>
                                <div class="mt-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ row.producto }}</div>
                                    <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                                        <div>
                                            <div class="uppercase text-gray-400">Cantidad</div>
                                            <div>{{ formatNumber(row.cantidad, 2) }}</div>
                                        </div>
                                        <div>
                                            <div class="uppercase text-gray-400">Precio</div>
                                            <div>{{ formatCurrency(row.precio_unitario) }}</div>
                                        </div>
                                        <div>
                                            <div class="uppercase text-gray-400">Total</div>
                                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(row.total) }}</div>
                                        </div>
                                        <div>
                                            <div class="uppercase text-gray-400">Cliente</div>
                                            <div>{{ row.cliente }}</div>
                                        </div>
                                        <div>
                                            <div class="uppercase text-gray-400">Stock anterior</div>
                                            <div>{{ displayStock(row.stock_anterior) }}</div>
                                        </div>
                                        <div>
                                            <div class="uppercase text-gray-400">Nuevo stock</div>
                                            <div>{{ displayStock(row.stock_nuevo) }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">Usuario: {{ row.usuario }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="hidden md:block max-h-[60vh] overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full text-xs text-left text-gray-700 dark:text-gray-200">
                                <thead class="sticky top-0 bg-gray-100 text-[11px] uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <tr>
                                        <th class="px-3 py-2">Id venta</th>
                                        <th class="px-3 py-2">Fecha</th>
                                        <th class="px-3 py-2">Tipo</th>
                                        <th class="px-3 py-2">Producto</th>
                                        <th class="px-3 py-2">Cantidad</th>
                                        <th class="px-3 py-2">Precio unitario</th>
                                        <th class="px-3 py-2">Total</th>
                                        <th class="px-3 py-2">Cliente</th>
                                        <th class="px-3 py-2">Stock anterior</th>
                                        <th class="px-3 py-2">Nuevo stock</th>
                                        <th class="px-3 py-2">Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, index) in ventasDiaData.rows" :key="index" class="border-t border-gray-200 dark:border-gray-700">
                                        <td class="px-3 py-2">
                                            <div>{{ row.venta_id }}</div>
                                            <span v-if="row.local_status" class="mt-1 inline-flex rounded bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-800">{{ row.local_status }}</span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ row.fecha_venta }}</td>
                                        <td class="px-3 py-2">{{ row.tipo_venta }}</td>
                                        <td class="px-3 py-2">{{ row.producto }}</td>
                                        <td class="px-3 py-2">{{ formatNumber(row.cantidad, 2) }}</td>
                                        <td class="px-3 py-2">{{ formatCurrency(row.precio_unitario) }}</td>
                                        <td class="px-3 py-2">{{ formatCurrency(row.total) }}</td>
                                        <td class="px-3 py-2">{{ row.cliente }}</td>
                                        <td class="px-3 py-2">{{ displayStock(row.stock_anterior) }}</td>
                                        <td class="px-3 py-2">{{ displayStock(row.stock_nuevo) }}</td>
                                        <td class="px-3 py-2">{{ row.usuario }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Total del día: {{ formatCurrency(ventasDiaData?.total ?? 0) }}
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="cerrarVentasDiaModal">Cerrar</SecondaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
