<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    empresas: { type: Array, default: () => [] },
    marcas: { type: Array, default: () => [] },
    clasificaciones: { type: Array, default: () => [] },
    auditoria: { type: Object, default: null },
});

const editando = computed(() => !!props.auditoria);
const filtros = props.auditoria?.alcance_filtros || {};

const form = useForm({
    id_empresa: props.auditoria?.id_empresa ?? (props.empresas.length === 1 ? props.empresas[0].id : ''),
    id_sucursal: props.auditoria?.id_sucursal ?? '',
    alcance_tipo: props.auditoria?.alcance_tipo ?? 'total',
    marcas: filtros.marcas ?? [],
    clasificaciones: filtros.clasificaciones ?? [],
    auditores: props.auditoria?.auditores ?? [],
    notas: props.auditoria?.notas ?? '',
});

const sucursales = ref([]);
const auditores = ref([]);
const cargandoSucursales = ref(false);
const busquedaMarca = ref('');
const busquedaClasificacion = ref('');

const cargarSucursales = async () => {
    sucursales.value = [];
    if (!form.id_empresa) return;
    cargandoSucursales.value = true;
    try {
        const { data } = await axios.get(route('auditorias.alcance.sucursales'), { params: { empresa: form.id_empresa } });
        sucursales.value = data.data;
    } finally {
        cargandoSucursales.value = false;
    }
};

const cargarAuditores = async () => {
    auditores.value = [];
    if (!form.id_sucursal) return;
    const { data } = await axios.get(route('auditorias.alcance.auditores'), { params: { sucursal: form.id_sucursal } });
    auditores.value = data.data;
    form.auditores = form.auditores.filter(id => auditores.value.some(a => a.id === id));
};

watch(() => form.id_empresa, () => {
    if (!editando.value) {
        form.id_sucursal = '';
    }
    cargarSucursales();
});
watch(() => form.id_sucursal, cargarAuditores);

onMounted(() => {
    cargarSucursales();
    if (form.id_sucursal) cargarAuditores();
});

const marcasFiltradas = computed(() => props.marcas.filter(m => m.nombre.toLowerCase().includes(busquedaMarca.value.toLowerCase())));
const clasificacionesFiltradas = computed(() => props.clasificaciones.filter(c => c.nombre.toLowerCase().includes(busquedaClasificacion.value.toLowerCase())));
const sucursalSeleccionada = computed(() => sucursales.value.find(s => s.id === form.id_sucursal));

const submit = () => {
    if (editando.value) {
        form.put(route('auditorias.update', props.auditoria.id));
    } else {
        form.post(route('auditorias.store'));
    }
};
</script>

<template>
    <AppLayout title="Nueva auditoría">
        <template #header>
            <PageHeader :title="editando ? `Editar ${auditoria.folio}` : 'Nueva auditoría'" subtitle="Elige qué vas a contar"
                :back="editando ? route('auditorias.show', auditoria.id) : route('auditorias.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="ui-card flex flex-col gap-5 p-4 sm:p-5" @submit.prevent="submit">
                <!-- 1. Empresa: sólo si el alcance cubre más de una -->
                <div v-if="empresas.length > 1 && !editando" class="ui-field">
                    <label class="ui-label" for="empresa">1. Empresa</label>
                    <select id="empresa" v-model="form.id_empresa" class="ui-input">
                        <option value="" disabled>Selecciona la empresa</option>
                        <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                    </select>
                    <InputError :message="form.errors.id_empresa" />
                </div>
                <div v-else-if="empresas.length === 1" class="ui-muted">Empresa: <strong>{{ empresas[0].nombre }}</strong></div>
                <div v-else-if="!empresas.length" class="ui-alert ui-alert-red">No tienes empresas asignadas. Pide al administrador que configure tu alcance.</div>

                <!-- 2. Sucursal / bodega -->
                <div class="ui-field">
                    <label class="ui-label">{{ empresas.length > 1 ? '2.' : '1.' }} Sucursal o bodega</label>
                    <p v-if="editando" class="ui-muted">{{ sucursalSeleccionada?.nombre ?? '—' }} (no se puede cambiar)</p>
                    <template v-else>
                        <p v-if="cargandoSucursales" class="ui-muted">Cargando…</p>
                        <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <label v-for="s in sucursales" :key="s.id" class="ui-check" :class="s.auditoria_activa ? 'opacity-60' : ''">
                                <input type="radio" v-model="form.id_sucursal" :value="s.id" :disabled="!!s.auditoria_activa">
                                <span class="flex-1">{{ s.nombre }}</span>
                                <span v-if="s.es_bodega" class="ui-badge ui-badge-blue">Bodega</span>
                                <span v-if="s.auditoria_activa" class="ui-badge ui-badge-amber">{{ s.auditoria_activa }}</span>
                            </label>
                        </div>
                        <p v-if="!cargandoSucursales && form.id_empresa && !sucursales.length" class="ui-muted">No hay sucursales en tu alcance para esta empresa.</p>
                    </template>
                    <InputError :message="form.errors.id_sucursal" />
                </div>

                <!-- 3. Alcance -->
                <fieldset class="ui-field">
                    <legend class="ui-label">Qué se cuenta</legend>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <label class="ui-check"><input type="radio" v-model="form.alcance_tipo" value="total"> Todo el inventario</label>
                        <label class="ui-check"><input type="radio" v-model="form.alcance_tipo" value="marcas"> Por marca</label>
                        <label class="ui-check"><input type="radio" v-model="form.alcance_tipo" value="clasificaciones"> Por clasificación</label>
                    </div>
                    <InputError :message="form.errors.alcance_tipo" />
                </fieldset>

                <div v-if="form.alcance_tipo === 'marcas'" class="ui-field">
                    <label class="ui-label" for="buscar-marca">Marcas ({{ form.marcas.length }} seleccionadas)</label>
                    <input id="buscar-marca" v-model="busquedaMarca" type="search" class="ui-input" placeholder="Buscar marca">
                    <div class="grid max-h-72 grid-cols-1 gap-2 overflow-y-auto sm:grid-cols-2">
                        <label v-for="m in marcasFiltradas" :key="m.id" class="ui-check">
                            <input type="checkbox" v-model="form.marcas" :value="m.id"> {{ m.nombre }}
                        </label>
                    </div>
                    <InputError :message="form.errors.marcas" />
                </div>

                <div v-if="form.alcance_tipo === 'clasificaciones'" class="ui-field">
                    <label class="ui-label" for="buscar-clasificacion">Clasificaciones ({{ form.clasificaciones.length }} seleccionadas)</label>
                    <input id="buscar-clasificacion" v-model="busquedaClasificacion" type="search" class="ui-input" placeholder="Buscar clasificación">
                    <div class="grid max-h-72 grid-cols-1 gap-2 overflow-y-auto sm:grid-cols-2">
                        <label v-for="c in clasificacionesFiltradas" :key="c.id" class="ui-check">
                            <input type="checkbox" v-model="form.clasificaciones" :value="c.id"> {{ c.nombre }}
                        </label>
                    </div>
                    <InputError :message="form.errors.clasificaciones" />
                </div>

                <!-- 4. Auditores adicionales -->
                <div v-if="auditores.length" class="ui-field">
                    <label class="ui-label">Otros auditores que contarán contigo</label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <label v-for="a in auditores" :key="a.id" class="ui-check">
                            <input type="checkbox" v-model="form.auditores" :value="a.id">
                            <span class="min-w-0"><span class="block">{{ a.name }}</span><span class="ui-help">{{ a.email }}</span></span>
                        </label>
                    </div>
                    <InputError :message="form.errors.auditores" />
                </div>

                <div class="ui-field">
                    <label class="ui-label" for="notas">Notas (opcional)</label>
                    <textarea id="notas" v-model="form.notas" rows="3" class="ui-input" placeholder="Ej. conteo de cierre de mes"></textarea>
                    <InputError :message="form.errors.notas" />
                </div>

                <div class="ui-alert ui-alert-blue">
                    Se guardará como borrador. Al <strong>iniciar</strong> se toma la foto del inventario del sistema y se bloquean transferencias,
                    compras, altas, dosificación, reseteos y devoluciones en la sucursal. Las ventas siguen.
                </div>

                <button class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="form.processing || !form.id_sucursal">
                    {{ editando ? 'Guardar cambios' : 'Crear borrador' }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>
