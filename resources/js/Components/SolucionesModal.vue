<script setup>
import { computed, reactive, ref, watch } from 'vue';
import axios from 'axios';
import InputError from '@/Components/InputError.vue';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
import { notify } from '@/utils/notify';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    producto: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const jsonConfig = { headers: { Accept: 'application/json' } };

const cargando = ref(false);
const errorCarga = ref('');
const soluciones = ref([]);
const enfermedadesFlor = ref([]);
const guardando = ref(false);
const errores = ref({});
const editando = ref(null);
const enfermedadSeleccionada = ref(null);
const formRef = ref(null);
const form = reactive({
    condiciones: '',
    dosis_bomba_ml: '',
    dosis_tambo_ml: '',
});

// Evita que una respuesta vieja pise la de otro producto si se abre y cierra rápido.
let peticionActual = 0;

const nombreSolucion = (solucion) => `${solucion.enfermedad?.nombre ?? '—'} en ${solucion.tipoFlor?.nombre ?? '—'}`;

// Guardar una enfermedad que ya tiene solución la actualiza (el servidor no duplica).
const solucionExistente = computed(() => {
    const id = enfermedadSeleccionada.value?.id;
    if (!id || editando.value) {
        return null;
    }
    return soluciones.value.find((solucion) => solucion.id_enfermedad_tipo_flor === id) ?? null;
});

const limpiarFormulario = () => {
    editando.value = null;
    enfermedadSeleccionada.value = null;
    form.condiciones = '';
    form.dosis_bomba_ml = '';
    form.dosis_tambo_ml = '';
    errores.value = {};
};

const cargar = async () => {
    const id = props.producto?.id;
    if (!id) {
        return;
    }
    const peticion = ++peticionActual;
    cargando.value = true;
    errorCarga.value = '';
    try {
        const { data } = await axios.get(route('solucion.index', { id_producto: id }), jsonConfig);
        if (peticion !== peticionActual) {
            return;
        }
        soluciones.value = data.soluciones ?? [];
        enfermedadesFlor.value = data.enfermedadesFlor ?? [];
    } catch (error) {
        if (peticion === peticionActual) {
            errorCarga.value = error?.response?.data?.message || 'No se pudieron cargar las soluciones.';
        }
    } finally {
        if (peticion === peticionActual) {
            cargando.value = false;
        }
    }
};

watch(() => props.show, (value) => {
    peticionActual++;
    limpiarFormulario();
    soluciones.value = [];
    if (value) {
        cargar();
    }
});

const editar = (solucion) => {
    editando.value = solucion;
    enfermedadSeleccionada.value = enfermedadesFlor.value.find((item) => item.id === solucion.id_enfermedad_tipo_flor) ?? null;
    form.condiciones = solucion.condiciones ?? '';
    form.dosis_bomba_ml = solucion.dosis_bomba_ml ?? '';
    form.dosis_tambo_ml = solucion.dosis_tambo_ml ?? '';
    errores.value = {};
    formRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const validar = () => {
    const nuevos = {};
    if (!enfermedadSeleccionada.value?.id) {
        nuevos.id_enfermedad_tipo_flor = 'Seleccione la enfermedad y flor que afecta';
    }
    if (!(Number(form.dosis_bomba_ml) > 0)) {
        nuevos.dosis_bomba_ml = 'Campo obligatorio (mayor a 0)';
    }
    if (!(Number(form.dosis_tambo_ml) > 0)) {
        nuevos.dosis_tambo_ml = 'Campo obligatorio (mayor a 0)';
    }
    errores.value = nuevos;
    return Object.keys(nuevos).length === 0;
};

const closeModal = () => {
    emit('close');
};

const submit = async () => {
    if (!props.producto?.id || guardando.value || !validar()) {
        return;
    }
    const payload = {
        id_producto: props.producto.id,
        id_enfermedad_tipo_flor: enfermedadSeleccionada.value.id,
        condiciones: form.condiciones,
        dosis_bomba_ml: form.dosis_bomba_ml,
        dosis_tambo_ml: form.dosis_tambo_ml,
    };
    const actualizar = Boolean(editando.value);
    guardando.value = true;
    try {
        if (actualizar) {
            await axios.put(route('solucion.update', editando.value.id), { ...payload, id: editando.value.id }, jsonConfig);
        } else {
            await axios.post(route('solucion.store'), payload, jsonConfig);
        }
        notify(actualizar || solucionExistente.value ? 'Solución actualizada correctamente.' : 'Solución agregada correctamente.', 'success');
        limpiarFormulario();
        await cargar();
    } catch (error) {
        const validacion = error?.response?.status === 422 ? error.response.data?.errors : null;
        if (validacion) {
            errores.value = Object.fromEntries(Object.entries(validacion).map(([campo, mensajes]) => [campo, [].concat(mensajes)[0]]));
        } else {
            notify(error?.response?.data?.message || 'No se pudo guardar la solución.', 'error');
        }
    } finally {
        guardando.value = false;
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/55 sm:items-center sm:p-6" @click.self="closeModal">
        <div role="dialog" aria-modal="true" aria-labelledby="modal-soluciones-title" @keydown.esc="closeModal"
            class="flex max-h-[92vh] w-full flex-col gap-4 overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:max-w-3xl sm:rounded-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="modal-soluciones-title" class="text-xl font-bold text-gray-900 dark:text-gray-100">Soluciones</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold">{{ producto?.nombre }}</span>
                        <template v-if="producto?.tamano"> ({{ producto.tamano }})</template>
                    </p>
                </div>
                <button type="button" @click="closeModal" aria-label="Cerrar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form ref="formRef" class="flex scroll-mt-4 flex-col gap-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700" @submit.prevent="submit">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="ui-section-title">{{ editando ? 'Editar solución' : 'Nueva solución' }}</h3>
                    <button v-if="editando" type="button" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400" @click="limpiarFormulario">
                        Cancelar edición
                    </button>
                </div>

                <div class="ui-field">
                    <label class="ui-label">Enfermedad y flor que afecta *</label>
                    <vue-single-select v-model="enfermedadSeleccionada" :options="enfermedadesFlor" option-key="id" option-label="nombre"
                        placeholder="Seleccione enfermedad" class="w-full" :disabled="cargando" />
                    <InputError :message="errores.id_enfermedad_tipo_flor" />
                    <p v-if="solucionExistente" class="ui-help">Ya hay una solución para esta enfermedad; al guardar se actualiza.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="ui-field">
                        <label for="solucion-dosis-bomba" class="ui-label">Dosis por bomba en ml/gr *</label>
                        <input id="solucion-dosis-bomba" type="number" step="0.01" min="0" inputmode="decimal" class="ui-input"
                            :class="{ 'ui-input-error': errores.dosis_bomba_ml }" v-model="form.dosis_bomba_ml" placeholder="Ej: 25.5">
                        <InputError :message="errores.dosis_bomba_ml" />
                        <p class="ui-help">Dosis recomendada por bomba de 20 litros</p>
                    </div>
                    <div class="ui-field">
                        <label for="solucion-dosis-tambo" class="ui-label">Dosis por tambo en ml/gr *</label>
                        <input id="solucion-dosis-tambo" type="number" step="0.01" min="0" inputmode="decimal" class="ui-input"
                            :class="{ 'ui-input-error': errores.dosis_tambo_ml }" v-model="form.dosis_tambo_ml" placeholder="Ej: 375">
                        <InputError :message="errores.dosis_tambo_ml" />
                        <p class="ui-help">Dosis recomendada por tambo de 300 litros</p>
                    </div>
                </div>

                <div class="ui-field">
                    <label for="solucion-condiciones" class="ui-label">Condiciones de aplicación</label>
                    <textarea id="solucion-condiciones" rows="2" class="ui-input" v-model="form.condiciones"></textarea>
                    <InputError :message="errores.condiciones" />
                </div>

                <button type="submit" class="ui-btn ui-btn-primary ui-btn-lg w-full sm:w-auto sm:self-end" :disabled="guardando || cargando">
                    {{ guardando ? 'Guardando...' : (editando || solucionExistente ? 'Actualizar solución' : 'Agregar solución') }}
                </button>
            </form>

            <section class="flex flex-col gap-3">
                <h3 class="ui-section-title">Soluciones registradas</h3>

                <div v-if="errorCarga" class="ui-alert ui-alert-red" role="alert">
                    <span>{{ errorCarga }}</span>
                    <button type="button" class="ml-auto font-semibold underline" @click="cargar">Reintentar</button>
                </div>
                <p v-else-if="cargando && !soluciones.length" class="ui-muted">Cargando soluciones...</p>
                <p v-else-if="!soluciones.length" class="ui-muted">Este producto aún no tiene soluciones registradas.</p>

                <ul v-else class="flex flex-col gap-2">
                    <li v-for="solucion in soluciones" :key="solucion.id"
                        class="flex items-start justify-between gap-3 rounded-xl border p-3"
                        :class="editando?.id === solucion.id ? 'border-indigo-500 bg-indigo-50 dark:border-indigo-400 dark:bg-indigo-950' : 'border-gray-200 dark:border-gray-700'">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ nombreSolucion(solucion) }}</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Bomba: <span class="font-semibold">{{ solucion.dosis_bomba_ml }}</span>
                                · Tambo: <span class="font-semibold">{{ solucion.dosis_tambo_ml }}</span>
                            </p>
                            <p v-if="solucion.condiciones" class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ solucion.condiciones }}</p>
                        </div>
                        <button type="button" class="ui-btn shrink-0" @click="editar(solucion)">Editar</button>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
