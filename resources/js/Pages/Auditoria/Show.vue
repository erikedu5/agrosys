<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import { formatFecha, formatMoneda } from '@/utils/formato';
import { statusBadge, cantidad, diferenciaClase, primerError } from '@/utils/auditoria';
import { notify } from '@/utils/notify';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    auditoria: { type: Object, required: true },
    permisos: { type: Object, default: () => ({}) },
    progreso: { type: Object, default: () => ({}) },
    transferenciasPendientes: { type: Number, default: 0 },
    reporte: { type: Object, default: null },
    filtros: { type: Object, default: () => ({}) },
    marcas: { type: Array, default: () => [] },
});

const a = computed(() => props.auditoria);
const status = computed(() => a.value.status);
const procesando = ref(false);
const errores = ref({});

const accion = (nombre, data = {}, onDone = null) => {
    procesando.value = true;
    errores.value = {};
    router.post(route(`auditorias.${nombre}`, a.value.id), data, {
        preserveScroll: true,
        onSuccess: () => { onDone?.(); },
        onError: (e) => { errores.value = e; notify(primerError(e), 'error'); },
        onFinish: () => { procesando.value = false; },
    });
};

// Inicio
const confirmaTransito = ref(false);
const iniciar = () => accion('iniciar', { confirmar_transito: confirmaTransito.value });

// Cierre de conteo
const modalCierre = ref(false);
const noContadosCero = ref(false);
const pendientes = computed(() => (props.progreso.total ?? 0) - (props.progreso.contados ?? 0));
const cerrarConteo = () => accion('cerrarConteo', { no_contados_cero: noContadosCero.value }, () => { modalCierre.value = false; });

// Reconteo
const seleccion = ref([]);
const solicitarReconteo = () => accion('reconteo', { items: seleccion.value }, () => { seleccion.value = []; });
const seleccionarDiferencias = () => {
    seleccion.value = (props.reporte?.marcas || []).flatMap(g => g.renglones)
        .filter(r => r.ronda === 1 && r.diferencia !== null && Number(r.diferencia) !== 0).map(r => r.id);
};

// Nota por renglón (p. ej. producto dañado o caducado)
const editarNota = (r) => {
    const comentario = window.prompt(`Nota para ${r.nombre}`, r.comentario || '');
    if (comentario === null) return;
    router.put(route('auditorias.items.comentario', { auditoria: a.value.id, item: r.id }), { comentario }, {
        preserveScroll: true,
        onSuccess: () => notify('Nota guardada'),
        onError: (e) => notify(primerError(e), 'error'),
    });
};

// Finalizar
const modalFinalizar = ref(false);
const finalizar = () => accion('finalizar', {}, () => { modalFinalizar.value = false; });

// Cancelar
const modalCancelar = ref(false);
const cancelForm = useForm({ motivo: '' });
const cancelar = () => {
    cancelForm.post(route('auditorias.cancelar', a.value.id), {
        preserveScroll: true,
        onSuccess: () => { modalCancelar.value = false; cancelForm.reset(); },
    });
};

// Filtros del reporte
const filtroMarca = ref(props.filtros.marca ?? '');
const filtroSku = ref(props.filtros.sku ?? '');
const soloDiferencias = ref(!!props.filtros.solo_diferencias);
const query = computed(() => {
    const q = {};
    if (filtroMarca.value) q.marca = filtroMarca.value;
    if (filtroSku.value) q.sku = filtroSku.value;
    if (soloDiferencias.value) q.solo_diferencias = 1;
    return q;
});
const aplicarFiltros = () => router.get(route('auditorias.show', a.value.id), query.value, { preserveState: true, preserveScroll: true, replace: true });
const urlReporte = (formato) => route(`auditorias.reporte.${formato}`, { auditoria: a.value.id, ...query.value });

const resumen = computed(() => props.reporte?.resumen);
const verCostos = computed(() => props.reporte?.ver_costos);
const enRevision = computed(() => status.value === 'en_revision');
const puedeOperar = computed(() => props.permisos.operar);

const fechas = computed(() => [
    { label: 'Creada', value: a.value.created_at },
    { label: 'Inicio de conteo', value: a.value.iniciada_at },
    { label: 'Cierre de conteo', value: a.value.conteo_cerrado_at },
    { label: 'Finalizada', value: a.value.finalizada_at },
    { label: 'Cancelada', value: a.value.cancelada_at },
].filter(f => f.value));
</script>

<template>
    <AppLayout :title="`Auditoría ${auditoria.folio}`">
        <template #header>
            <PageHeader :title="`Auditoría ${auditoria.folio}`" :subtitle="`${auditoria.empresa} · ${auditoria.es_bodega ? 'Bodega' : 'Sucursal'} ${auditoria.sucursal}`" :back="route('auditorias.index')">
                <span :class="statusBadge(auditoria.status)">{{ auditoria.status_label }}</span>
            </PageHeader>
        </template>

        <div class="ui-page">
            <div v-if="Object.keys(errores).length" class="ui-alert ui-alert-red" role="alert">{{ primerError(errores) }}</div>

            <!-- Encabezado -->
            <section class="ui-card grid grid-cols-2 gap-x-4 gap-y-3 p-4 text-sm sm:grid-cols-4">
                <div><p class="ui-help">Auditores</p><p class="font-semibold">{{ auditoria.auditores.join(', ') || '—' }}</p></div>
                <div><p class="ui-help">Abierta por</p><p class="font-semibold">{{ auditoria.creador || '—' }}</p></div>
                <div v-for="f in fechas" :key="f.label"><p class="ui-help">{{ f.label }}</p><p class="font-semibold">{{ formatFecha(f.value, true) }}</p></div>
                <div v-if="reporte" class="col-span-2"><p class="ui-help">Alcance</p><p class="font-semibold">{{ reporte.auditoria.alcance }}</p></div>
                <div v-if="auditoria.notas" class="col-span-2 sm:col-span-4"><p class="ui-help">Notas</p><p>{{ auditoria.notas }}</p></div>
                <div v-if="auditoria.motivo_cancelacion" class="col-span-2 sm:col-span-4"><p class="ui-help">Motivo de cancelación</p><p>{{ auditoria.motivo_cancelacion }}</p></div>
            </section>

            <!-- Borrador -->
            <section v-if="status === 'borrador'" class="ui-card flex flex-col gap-3 p-4">
                <h2 class="ui-section-title">Listo para iniciar</h2>
                <p class="ui-muted">Al iniciar se toma la foto del inventario del sistema y se bloquean en esta sucursal los movimientos distintos a ventas.</p>
                <div v-if="transferenciasPendientes > 0" class="ui-alert">
                    <div class="flex flex-col gap-2">
                        <span>Hay {{ transferenciasPendientes }} transferencia(s) pendiente(s) desde o hacia esta sucursal. Lo ideal es recibirlas antes de iniciar; quedarán como "en tránsito" en el reporte.</span>
                        <label class="flex items-center gap-2 font-semibold normal-case"><input type="checkbox" v-model="confirmaTransito"> Iniciar de todos modos</label>
                    </div>
                </div>
                <div v-if="puedeOperar" class="flex flex-wrap gap-2">
                    <button type="button" class="ui-btn ui-btn-primary ui-btn-lg flex-1 sm:flex-none" :disabled="procesando || (transferenciasPendientes > 0 && !confirmaTransito)" @click="iniciar">Iniciar conteo</button>
                    <Link :href="route('auditorias.edit', auditoria.id)" class="ui-btn ui-btn-lg flex-1 sm:flex-none">Editar</Link>
                </div>
            </section>

            <!-- En conteo: ciego, sólo progreso -->
            <section v-if="status === 'en_conteo'" class="ui-card flex flex-col gap-3 p-4">
                <h2 class="ui-section-title">Conteo en curso</h2>
                <p class="text-2xl font-bold">{{ progreso.contados }} / {{ progreso.total }} <span class="ui-muted font-normal">productos contados</span></p>
                <div class="h-3 w-full overflow-hidden rounded bg-gray-100 dark:bg-gray-700">
                    <div class="h-3 bg-green-600" :style="{ width: (progreso.total ? progreso.contados * 100 / progreso.total : 0) + '%' }"></div>
                </div>
                <p v-if="progreso.no_esperados" class="ui-muted">{{ progreso.no_esperados }} producto(s) encontrados fuera del alcance o sin registro.</p>
                <p class="ui-muted">Conteo ciego: las existencias del sistema se muestran al cerrar el conteo.</p>
                <div v-if="puedeOperar" class="flex flex-wrap gap-2">
                    <Link :href="route('auditorias.captura', auditoria.id)" class="ui-btn ui-btn-primary ui-btn-lg flex-1 sm:flex-none">Ir a captura</Link>
                    <button type="button" class="ui-btn ui-btn-lg flex-1 sm:flex-none" :disabled="procesando" @click="modalCierre = true; noContadosCero = false">Cerrar conteo</button>
                </div>
            </section>

            <!-- Resultado (revisión, finalizada o cancelada tras conteo) -->
            <template v-if="reporte">
                <section class="grid grid-cols-2 gap-3 sm:grid-cols-4" :class="verCostos ? 'lg:grid-cols-7' : 'lg:grid-cols-5'">
                    <div class="ui-card p-3"><p class="ui-help">Exactitud</p><p class="text-xl font-bold">{{ resumen.exactitud !== null ? resumen.exactitud + '%' : '—' }}</p><p class="ui-help">{{ resumen.exactos }} de {{ resumen.conciliados }} sin diferencia</p></div>
                    <div class="ui-card p-3"><p class="ui-help">Faltantes</p><p class="text-xl font-bold text-red-700 dark:text-red-300">{{ cantidad(resumen.unidades_faltantes) }}</p></div>
                    <div class="ui-card p-3"><p class="ui-help">Sobrantes</p><p class="text-xl font-bold text-blue-700 dark:text-blue-300">{{ cantidad(resumen.unidades_sobrantes) }}</p></div>
                    <div class="ui-card p-3"><p class="ui-help">Neto unidades</p><p class="text-xl font-bold" :class="diferenciaClase(resumen.unidades_neto)">{{ cantidad(resumen.unidades_neto) }}</p></div>
                    <div class="ui-card p-3"><p class="ui-help">Renglones</p><p class="text-xl font-bold">{{ resumen.renglones }}</p><p class="ui-help">de {{ resumen.renglones_total }} en la auditoría</p></div>
                    <template v-if="verCostos">
                        <div class="ui-card p-3"><p class="ui-help">Valor faltante</p><p class="text-xl font-bold text-red-700 dark:text-red-300">{{ formatMoneda(resumen.valor_faltante) }}</p></div>
                        <div class="ui-card p-3"><p class="ui-help">Valor neto</p><p class="text-xl font-bold" :class="diferenciaClase(resumen.valor_neto)">{{ formatMoneda(resumen.valor_neto) }}</p></div>
                    </template>
                </section>

                <!-- Acciones de revisión -->
                <section v-if="enRevision && puedeOperar" class="ui-card flex flex-col gap-3 p-4">
                    <h2 class="ui-section-title">Revisión</h2>
                    <p class="ui-muted">Marca los productos con diferencia que quieras recontar. El reconteo reemplaza la primera captura y ambas quedan en el reporte. Al finalizar se recalcula con ventas offline que hayan llegado después.</p>
                    <p v-if="progreso.reconteo_pendiente" class="ui-alert">{{ progreso.reconteo_pendiente }} producto(s) esperan reconteo. Si finalizas sin recontarlos, se conserva la primera captura.</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="ui-btn flex-1 sm:flex-none" @click="seleccionarDiferencias">Seleccionar con diferencia</button>
                        <button type="button" class="ui-btn ui-btn-primary flex-1 sm:flex-none" :disabled="procesando || !seleccion.length" @click="solicitarReconteo">Recontar {{ seleccion.length || '' }}</button>
                        <Link v-if="progreso.reconteo_pendiente" :href="route('auditorias.captura', auditoria.id)" class="ui-btn flex-1 sm:flex-none">Ir a reconteo</Link>
                        <button type="button" class="ui-btn ui-btn-success flex-1 sm:flex-none" :disabled="procesando" @click="modalFinalizar = true">Finalizar auditoría</button>
                    </div>
                </section>

                <!-- Filtros y descargas -->
                <section class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:flex-wrap sm:items-end">
                    <div class="ui-field sm:w-56">
                        <label class="ui-label" for="f-marca">Marca</label>
                        <select id="f-marca" v-model="filtroMarca" class="ui-input" @change="aplicarFiltros">
                            <option value="">Todas</option>
                            <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                        </select>
                    </div>
                    <div class="ui-field sm:w-56">
                        <label class="ui-label" for="f-sku">SKU / código</label>
                        <input id="f-sku" v-model="filtroSku" type="search" class="ui-input" placeholder="Código de barras o ID" @keydown.enter.prevent="aplicarFiltros">
                    </div>
                    <label class="ui-check sm:w-auto"><input type="checkbox" v-model="soloDiferencias" @change="aplicarFiltros"> Sólo diferencias</label>
                    <span class="flex-1"></span>
                    <a :href="urlReporte('xlsx')" class="ui-btn">Excel</a>
                </section>

                <!-- Detalle por marca -->
                <section v-for="grupo in reporte.marcas" :key="grupo.marca" class="ui-card overflow-hidden">
                    <header class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                        <h3 class="font-bold">{{ grupo.marca }}</h3>
                        <p class="ui-help">
                            {{ grupo.totales.renglones }} renglones · exactitud {{ grupo.totales.exactitud ?? '—' }}% ·
                            neto <span :class="diferenciaClase(grupo.totales.unidades_neto)">{{ cantidad(grupo.totales.unidades_neto) }}</span>
                            <template v-if="verCostos"> · {{ formatMoneda(grupo.totales.valor_neto) }}</template>
                        </p>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600 dark:bg-gray-900 dark:text-gray-400">
                                <tr>
                                    <th v-if="enRevision && puedeOperar" class="px-3 py-2"><span class="sr-only">Recontar</span></th>
                                    <th class="px-3 py-2">Producto</th>
                                    <th class="px-3 py-2 text-right">Sistema inicio</th>
                                    <th class="px-3 py-2 text-right" title="Movimientos del sistema entre el inicio y la captura">Mov. durante</th>
                                    <th class="px-3 py-2 text-right" title="Ventas offline anteriores a la captura sincronizadas después">Ventas tardías</th>
                                    <th class="px-3 py-2 text-right">Esperado</th>
                                    <th class="px-3 py-2 text-right">Contado</th>
                                    <th class="px-3 py-2 text-right">Diferencia</th>
                                    <th v-if="verCostos" class="px-3 py-2 text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in grupo.renglones" :key="r.id" class="border-t border-gray-100 dark:border-gray-700">
                                    <td v-if="enRevision && puedeOperar" class="px-3 py-2">
                                        <input v-if="r.ronda === 1 && r.id_producto" type="checkbox" v-model="seleccion" :value="r.id" :aria-label="`Recontar ${r.nombre}`">
                                        <span v-else-if="r.ronda === 2" class="ui-badge ui-badge-amber">R2</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="font-semibold">{{ r.nombre }} <span class="ui-muted">{{ r.tamano }}</span></p>
                                        <p class="ui-help">
                                            SKU {{ r.sku }}
                                            <span v-if="r.no_esperado" class="ui-badge ui-badge-blue ml-1">No esperado</span>
                                            <span v-if="r.ronda === 2 && r.contado_r1 !== null" class="ml-1">· R1: {{ cantidad(r.contado_r1) }}</span>
                                            <span v-if="Number(r.transito_salida) || Number(r.transito_entrada)" class="ml-1">· tránsito −{{ cantidad(r.transito_salida) }} / +{{ cantidad(r.transito_entrada) }}</span>
                                        </p>
                                        <p v-if="r.comentario" class="ui-help italic">{{ r.comentario }}</p>
                                        <button v-if="enRevision && puedeOperar" type="button" class="text-xs font-semibold text-blue-700 underline dark:text-blue-300" @click="editarNota(r)">
                                            {{ r.comentario ? 'Editar nota' : 'Agregar nota' }}
                                        </button>
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ cantidad(r.sistema_inicio) }}</td>
                                    <td class="px-3 py-2 text-right">{{ cantidad(r.movimientos_durante) }}</td>
                                    <td class="px-3 py-2 text-right">{{ cantidad(r.ventas_offline_tardias) }}</td>
                                    <td class="px-3 py-2 text-right">{{ cantidad(r.esperado) }}</td>
                                    <td class="px-3 py-2 text-right font-semibold">{{ r.estado === 'reconteo' ? 'Pendiente' : cantidad(r.contado) }}</td>
                                    <td class="px-3 py-2 text-right" :class="diferenciaClase(r.diferencia)">{{ cantidad(r.diferencia) }}</td>
                                    <td v-if="verCostos" class="px-3 py-2 text-right" :class="diferenciaClase(r.valor_diferencia)">{{ r.valor_diferencia !== null ? formatMoneda(r.valor_diferencia) : '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                <p v-if="!reporte.marcas.length" class="ui-muted">Sin renglones para los filtros seleccionados.</p>

                <section v-if="reporte.movimientos_durante.length" class="ui-card p-4">
                    <h3 class="font-bold">Movimientos del sistema durante el conteo</h3>
                    <ul class="mt-2 grid grid-cols-1 gap-1 text-sm sm:grid-cols-3">
                        <li v-for="m in reporte.movimientos_durante" :key="m.tipo">{{ m.etiqueta }}: <strong>{{ cantidad(m.cantidad) }}</strong></li>
                    </ul>
                </section>
            </template>

            <div v-if="permisos.cancelar" class="flex justify-end">
                <button type="button" class="ui-btn ui-btn-danger" @click="modalCancelar = true">Cancelar auditoría</button>
            </div>
        </div>

        <DialogModal :show="modalCierre" @close="modalCierre = false">
            <template #title>Cerrar conteo</template>
            <template #content>
                <p>Después de cerrar ya no se podrán capturar productos nuevos; sólo reconteos de los que marques.</p>
                <div v-if="pendientes > 0" class="ui-alert mt-3">
                    <label class="flex items-start gap-2 normal-case">
                        <input type="checkbox" v-model="noContadosCero" class="mt-1">
                        <span>Hay <strong>{{ pendientes }}</strong> producto(s) sin contar. Confirmo que no hay existencias físicas y se registren en <strong>cero</strong>.</span>
                    </label>
                </div>
                <InputError :message="errores.pendientes" class="mt-2" />
            </template>
            <template #footer>
                <button type="button" class="ui-btn" @click="modalCierre = false">Volver</button>
                <button type="button" class="ui-btn ui-btn-primary ml-2" :disabled="procesando || (pendientes > 0 && !noContadosCero)" @click="cerrarConteo">Cerrar conteo</button>
            </template>
        </DialogModal>

        <DialogModal :show="modalFinalizar" @close="modalFinalizar = false">
            <template #title>Finalizar auditoría</template>
            <template #content>
                <p>El reporte quedará cerrado y se liberará el bloqueo de movimientos en la sucursal. El sistema <strong>no ajusta</strong> el inventario; los ajustes se hacen aparte con las herramientas de inventario.</p>
            </template>
            <template #footer>
                <button type="button" class="ui-btn" @click="modalFinalizar = false">Volver</button>
                <button type="button" class="ui-btn ui-btn-success ml-2" :disabled="procesando" @click="finalizar">Finalizar</button>
            </template>
        </DialogModal>

        <DialogModal :show="modalCancelar" @close="modalCancelar = false">
            <template #title>Cancelar auditoría</template>
            <template #content>
                <div class="ui-field">
                    <label class="ui-label" for="motivo">Motivo</label>
                    <textarea id="motivo" v-model="cancelForm.motivo" rows="3" class="ui-input"></textarea>
                    <InputError :message="cancelForm.errors.motivo || cancelForm.errors.status" />
                </div>
            </template>
            <template #footer>
                <button type="button" class="ui-btn" @click="modalCancelar = false">Volver</button>
                <button type="button" class="ui-btn ui-btn-danger ml-2" :disabled="cancelForm.processing" @click="cancelar">Cancelar auditoría</button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
