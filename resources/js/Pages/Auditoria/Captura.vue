<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { cantidad, primerError } from '@/utils/auditoria';
import { notify } from '@/utils/notify';
import { formatFecha } from '@/utils/formato';
import { Link } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    auditoria: { type: Object, required: true },
    progreso: { type: Object, default: () => ({}) },
    capturas: { type: Array, default: () => [] },
    reconteo: { type: Array, default: () => [] },
});

const enRevision = computed(() => props.auditoria.status === 'en_revision');
const progreso = ref({ ...props.progreso });
const capturas = ref([...props.capturas]);
const reconteo = ref([...props.reconteo]);

const busqueda = ref('');
const resultados = ref([]);
const buscando = ref(false);
const seleccionado = ref(null);
const cantidadInput = ref('');
const ubicacion = ref(leerUbicacion());
const libre = ref(false);
const descripcionLibre = ref('');
const guardando = ref(false);
const error = ref('');

const inputBusqueda = ref(null);
const inputCantidad = ref(null);

function leerUbicacion() {
    try { return localStorage.getItem(`auditoria-ubicacion-${props.auditoria.id}`) || ''; } catch { return ''; }
}
const recordarUbicacion = () => {
    try { localStorage.setItem(`auditoria-ubicacion-${props.auditoria.id}`, ubicacion.value || ''); } catch { /* sin almacenamiento */ }
};

const enfocarBusqueda = () => nextTick(() => inputBusqueda.value?.focus());
const enfocarCantidad = () => nextTick(() => { inputCantidad.value?.focus(); inputCantidad.value?.select?.(); });

const buscar = async () => {
    const q = busqueda.value.trim();
    error.value = '';
    if (!q) return;
    buscando.value = true;
    try {
        const { data } = await axios.get(route('auditorias.buscarProducto', props.auditoria.id), { params: { q } });
        resultados.value = data.data;
        // Lector de código: coincidencia exacta o único resultado se selecciona directo.
        const exacto = resultados.value.find(r => r.exacto);
        if (exacto || resultados.value.length === 1) {
            seleccionar(exacto || resultados.value[0]);
        } else if (!resultados.value.length) {
            error.value = `No se encontró "${q}". Puedes registrarlo como producto no registrado.`;
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'No se pudo buscar.';
    } finally {
        buscando.value = false;
    }
};

const seleccionar = (producto) => {
    if (enRevision.value && producto.ronda !== 2) {
        error.value = 'En revisión sólo se capturan productos marcados para reconteo.';
        return;
    }
    seleccionado.value = producto;
    libre.value = false;
    resultados.value = [];
    busqueda.value = '';
    cantidadInput.value = '';
    enfocarCantidad();
};

const seleccionarReconteo = (item) => seleccionar({ id_item: item.id, id_producto: item.id_producto, nombre: item.nombre, tamano: item.tamano, marca: item.marca, barcode: item.sku, contado: item.contado, ronda: 2, en_alcance: true });

const modoLibre = () => {
    seleccionado.value = null;
    libre.value = true;
    descripcionLibre.value = busqueda.value.trim();
    resultados.value = [];
    enfocarCantidad();
};

const limpiar = () => {
    seleccionado.value = null;
    libre.value = false;
    descripcionLibre.value = '';
    cantidadInput.value = '';
    enfocarBusqueda();
};

const aplicarRespuesta = (data) => {
    progreso.value = data.progreso;
    capturas.value = data.capturas;
    const idx = reconteo.value.findIndex(i => i.id === data.item.id);
    if (idx >= 0) reconteo.value[idx] = data.item;
};

const guardar = async () => {
    error.value = '';
    if (cantidadInput.value === '' || Number(cantidadInput.value) < 0) {
        error.value = 'Captura la cantidad contada.';
        enfocarCantidad();
        return;
    }
    guardando.value = true;
    recordarUbicacion();
    const payload = { cantidad: cantidadInput.value, ubicacion: ubicacion.value || null };
    if (libre.value) {
        payload.descripcion_libre = descripcionLibre.value;
    } else if (seleccionado.value?.id_item) {
        payload.id_item = seleccionado.value.id_item;
    } else {
        payload.id_producto = seleccionado.value?.id_producto;
    }
    try {
        const { data } = await axios.post(route('auditorias.conteos.store', props.auditoria.id), payload);
        aplicarRespuesta(data);
        notify(`${data.item.nombre}: total contado ${cantidad(data.item.contado)}`);
        limpiar();
    } catch (e) {
        error.value = e.response?.data?.errors ? primerError(e.response.data.errors) : (e.response?.data?.message || 'No se pudo guardar la captura.');
    } finally {
        guardando.value = false;
    }
};

const anular = async (captura) => {
    if (!confirm(`¿Anular la captura de ${captura.nombre} (${cantidad(captura.cantidad)})?`)) return;
    try {
        const { data } = await axios.post(route('auditorias.conteos.anular', { auditoria: props.auditoria.id, conteo: captura.id }));
        aplicarRespuesta(data);
    } catch (e) {
        error.value = e.response?.data?.errors ? primerError(e.response.data.errors) : (e.response?.data?.message || 'No se pudo anular.');
    }
};

const porcentaje = computed(() => (progreso.value.total ? Math.round(progreso.value.contados * 100 / progreso.value.total) : 0));

onMounted(enfocarBusqueda);
</script>

<template>
    <AppLayout :title="`Captura ${auditoria.folio}`">
        <template #header>
            <PageHeader :title="enRevision ? 'Reconteo' : 'Captura de conteo'" :subtitle="`${auditoria.folio} · ${auditoria.es_bodega ? 'Bodega' : 'Sucursal'} ${auditoria.sucursal}`" :back="route('auditorias.show', auditoria.id)" />
        </template>

        <div class="ui-page ui-page-narrow">
            <!-- Progreso -->
            <section class="ui-card flex flex-col gap-2 p-4">
                <div class="flex items-baseline justify-between gap-2">
                    <p class="text-lg font-bold">{{ progreso.contados }} / {{ progreso.total }} contados</p>
                    <p class="ui-muted">{{ porcentaje }}%</p>
                </div>
                <div class="h-3 w-full overflow-hidden rounded bg-gray-100 dark:bg-gray-700">
                    <div class="h-3 bg-green-600" :style="{ width: porcentaje + '%' }"></div>
                </div>
                <p v-if="enRevision" class="ui-muted">{{ progreso.reconteo_pendiente }} producto(s) por recontar.</p>
                <p v-else class="ui-muted">Conteo ciego: cuenta lo que ves físicamente. Si el producto está en varios lugares, captura cada ubicación por separado.</p>
            </section>

            <!-- Lista de reconteo -->
            <section v-if="enRevision" class="ui-card overflow-hidden">
                <h2 class="px-4 py-3 font-bold">Productos a recontar</h2>
                <button v-for="item in reconteo" :key="item.id" type="button" class="ui-row w-full text-left hover:bg-gray-50 dark:hover:bg-gray-700" @click="seleccionarReconteo(item)">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ item.nombre }} <span class="ui-muted">{{ item.tamano }}</span></span>
                        <span class="ui-help">SKU {{ item.sku }}</span>
                    </span>
                    <span v-if="item.estado === 'reconteo'" class="ui-badge ui-badge-amber">Pendiente</span>
                    <span v-else class="ui-badge ui-badge-green">{{ cantidad(item.contado) }}</span>
                </button>
                <p v-if="!reconteo.length" class="ui-row ui-muted">No hay productos marcados para reconteo.</p>
            </section>

            <!-- Búsqueda / escaneo -->
            <section v-if="!seleccionado && !libre" class="ui-card flex flex-col gap-3 p-4">
                <label class="ui-label" for="buscar">Escanea o busca el producto</label>
                <div class="flex gap-2">
                    <input id="buscar" ref="inputBusqueda" v-model="busqueda" type="search" enterkeyhint="search" autocomplete="off"
                        class="ui-input h-14 flex-1 text-lg" placeholder="Código de barras, nombre o ID" @keydown.enter.prevent="buscar">
                    <button type="button" class="ui-btn ui-btn-primary ui-btn-lg" :disabled="buscando" @click="buscar">Buscar</button>
                </div>
                <div v-if="resultados.length" class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    <button v-for="r in resultados" :key="r.id_producto" type="button" class="ui-row w-full text-left hover:bg-gray-50 dark:hover:bg-gray-700" @click="seleccionar(r)">
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ r.nombre }} <span class="ui-muted">{{ r.tamano }}</span></span>
                            <span class="ui-help">{{ r.marca || 'Sin marca' }} · {{ r.barcode || `ID ${r.id_producto}` }}</span>
                        </span>
                        <span v-if="!r.en_alcance" class="ui-badge ui-badge-blue">Fuera de alcance</span>
                        <span v-if="r.contado !== null && r.contado !== undefined" class="ui-badge ui-badge-green">Contado {{ cantidad(r.contado) }}</span>
                    </button>
                </div>
                <button v-if="!enRevision" type="button" class="ui-btn self-start" @click="modoLibre">Producto no registrado</button>
            </section>

            <!-- Captura de cantidad -->
            <form v-else class="ui-card flex flex-col gap-4 p-4" @submit.prevent="guardar">
                <div>
                    <p class="ui-help">{{ libre ? 'Producto no registrado en catálogo' : (seleccionado.marca || 'Sin marca') }}</p>
                    <p v-if="!libre" class="text-lg font-bold">{{ seleccionado.nombre }} <span class="ui-muted">{{ seleccionado.tamano }}</span></p>
                    <p v-if="!libre" class="ui-help">{{ seleccionado.barcode || `ID ${seleccionado.id_producto}` }}
                        <template v-if="seleccionado.contado !== null && seleccionado.contado !== undefined"> · ya contado: {{ cantidad(seleccionado.contado) }} (esta captura se suma)</template>
                    </p>
                    <p v-if="!libre && !seleccionado.en_alcance" class="ui-alert mt-2">Este producto no estaba en el alcance; quedará como "no esperado" en el reporte.</p>
                </div>
                <div v-if="libre" class="ui-field">
                    <label class="ui-label" for="desc">Descripción</label>
                    <input id="desc" v-model="descripcionLibre" class="ui-input" placeholder="Nombre, marca, presentación" required>
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="cantidad">Cantidad contada</label>
                    <input id="cantidad" ref="inputCantidad" v-model="cantidadInput" type="number" inputmode="decimal" step="0.01" min="0"
                        class="ui-input h-16 text-center text-3xl font-bold" required>
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="ubicacion">Ubicación (opcional)</label>
                    <input id="ubicacion" v-model="ubicacion" class="ui-input" placeholder="Ej. Pasillo 3, anaquel B" maxlength="120">
                </div>
                <div class="flex gap-2">
                    <button type="button" class="ui-btn ui-btn-lg flex-1" @click="limpiar">Cancelar</button>
                    <button type="submit" class="ui-btn ui-btn-primary ui-btn-lg flex-1" :disabled="guardando">Guardar</button>
                </div>
            </form>

            <div v-if="error" class="ui-alert ui-alert-red" role="alert">{{ error }}</div>

            <!-- Mis capturas -->
            <section class="ui-card overflow-hidden">
                <h2 class="px-4 py-3 font-bold">Mis capturas recientes</h2>
                <div v-for="c in capturas" :key="c.id" class="ui-row" :class="c.anulado ? 'opacity-50' : ''">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold" :class="c.anulado ? 'line-through' : ''">{{ c.nombre }} <span class="ui-muted">{{ c.tamano }}</span></span>
                        <span class="ui-help">{{ c.ubicacion || 'Sin ubicación' }} · {{ formatFecha(c.created_at, true) }}<template v-if="c.ronda === 2"> · reconteo</template></span>
                    </span>
                    <span class="text-lg font-bold">{{ cantidad(c.cantidad) }}</span>
                    <button v-if="!c.anulado && (enRevision ? c.ronda === 2 : c.ronda === 1)" type="button" class="ui-btn ui-btn-danger" @click="anular(c)">Anular</button>
                    <span v-else-if="c.anulado" class="ui-badge">Anulada</span>
                </div>
                <p v-if="!capturas.length" class="ui-row ui-muted">Aún no hay capturas.</p>
            </section>

            <Link :href="route('auditorias.show', auditoria.id)" class="ui-btn self-center">Ver auditoría</Link>
        </div>
    </AppLayout>
</template>
