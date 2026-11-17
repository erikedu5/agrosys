<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { notify } from '@/utils/notify';
import { TIPOS_PRECIO } from '@/utils/precios';

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

const medidaInput = ref(null);
const preciosEditados = ref(false);

const form = useForm({
    contenido: '',
    medida: '',
    unidad: '',
    acepta_perdida: false,
    precio_publico: '',
    precio_preferencial_1: '',
    precio_preferencial_2: '',
});

// Cantidades en centésimas para no arrastrar errores de punto flotante.
const centesimas = (valor) => {
    const numero = Number(valor);
    if (valor === '' || valor === null || !Number.isFinite(numero) || numero <= 0) {
        return 0;
    }
    return Math.round(numero * 100);
};
const texto = (unidades) => String(unidades / 100);

const contenido = computed(() => centesimas(form.contenido));
const medida = computed(() => centesimas(form.medida));
const medidas = computed(() => (contenido.value && medida.value ? Math.floor(contenido.value / medida.value) : 0));
const sobrante = computed(() => (medidas.value ? contenido.value - medidas.value * medida.value : 0));
const nombreMedida = computed(() => `Medida ${props.producto?.nombre ?? ''}`);
const puedeGuardar = computed(() => medidas.value >= 1 && form.unidad.trim() !== ''
    && (sobrante.value === 0 || form.acepta_perdida) && !form.processing);

// El tamaño es texto libre ("1000ml", "1 L"); se propone lo que se pueda leer.
const leerTamano = (tamano) => {
    const partes = String(tamano ?? '').trim().match(/^(\d+(?:[.,]\d+)?)\s*(\D.*)?$/);
    return partes ? { contenido: partes[1].replace(',', '.'), unidad: (partes[2] ?? '').trim().slice(0, 20) } : { contenido: '', unidad: '' };
};

watch(() => props.show, (value) => {
    if (!value) {
        return;
    }
    form.reset();
    form.clearErrors();
    preciosEditados.value = false;
    Object.assign(form, leerTamano(props.producto?.tamano));
    nextTick(() => medidaInput.value?.focus());
});

// Precio sugerido: el del envase repartido entre las medidas, hasta que el usuario lo cambie.
watch(medidas, (cantidad) => {
    if (preciosEditados.value) {
        return;
    }
    TIPOS_PRECIO.forEach(({ campo }) => {
        const precio = Number(props.producto?.[campo]);
        form[campo] = cantidad >= 1 && precio > 0 ? Math.max(precio / cantidad, 0.01).toFixed(2) : '';
    });
});

watch(sobrante, () => {
    form.acepta_perdida = false;
});

const closeModal = () => {
    emit('close');
};

const submit = () => {
    if (!props.producto?.id || !puedeGuardar.value) {
        return;
    }
    const cantidad = medidas.value;
    const tamano = `${texto(medida.value)} ${form.unidad.trim()}`;
    form.clearErrors();
    form.post(route('inventario.dosificar', props.producto.id), {
        preserveScroll: true,
        onSuccess: () => {
            notify(`Se agregaron ${cantidad} medidas de ${tamano} a "${nombreMedida.value}".`, 'success');
            closeModal();
        },
        onError: (errors) => {
            ['producto', 'bloqueo'].forEach((campo) => {
                if (errors?.[campo]) {
                    notify(errors[campo], 'error');
                }
            });
        },
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/55 sm:items-center sm:p-6" @click.self="closeModal">
        <form role="dialog" aria-modal="true" aria-labelledby="modal-dosificar-title" @submit.prevent="submit"
            class="flex max-h-[92vh] w-full flex-col gap-4 overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:max-w-lg sm:rounded-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="modal-dosificar-title" class="text-xl font-bold text-gray-900 dark:text-gray-100">Dosificar producto</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Se dará de baja 1 unidad de <span class="font-semibold">{{ producto?.nombre }}</span>
                        <template v-if="producto?.tamano"> ({{ producto.tamano }})</template>
                        y se darán de alta sus medidas.
                    </p>
                </div>
                <button type="button" @click="closeModal" aria-label="Cerrar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="ui-field">
                    <label for="dosificar-contenido" class="ui-label">Contenido del envase</label>
                    <input id="dosificar-contenido" type="number" min="0.01" step="0.01" inputmode="decimal" required v-model="form.contenido" class="ui-input" placeholder="1000" />
                    <InputError class="mt-1" :message="form.errors.contenido" />
                </div>
                <div class="ui-field">
                    <label for="dosificar-unidad" class="ui-label">Unidad</label>
                    <input id="dosificar-unidad" type="text" maxlength="20" required v-model="form.unidad" class="ui-input" placeholder="ml" />
                    <InputError class="mt-1" :message="form.errors.unidad" />
                </div>
            </div>

            <div class="ui-field">
                <label for="dosificar-medida" class="ui-label">Tamaño de cada medida{{ form.unidad.trim() ? ` (${form.unidad.trim()})` : '' }}</label>
                <input id="dosificar-medida" ref="medidaInput" type="number" min="0.01" step="0.01" inputmode="decimal" required v-model="form.medida" class="ui-input" placeholder="250" />
                <InputError class="mt-1" :message="form.errors.medida" />
            </div>

            <p v-if="medidas >= 1" class="ui-alert ui-alert-blue" role="status">
                <span>
                    Salen <span class="font-bold">{{ medidas }}</span> {{ medidas === 1 ? 'medida' : 'medidas' }} de
                    {{ texto(medida) }} {{ form.unidad.trim() }} en <span class="font-semibold">{{ nombreMedida }}</span>.
                </span>
            </p>
            <p v-else-if="contenido && medida" class="ui-alert ui-alert-red" role="alert">
                La medida no puede ser mayor que el contenido del envase.
            </p>

            <div v-if="sobrante > 0" class="flex flex-col gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-600 dark:bg-amber-950 dark:text-amber-100" role="alert">
                <p>
                    <span class="font-semibold">Sobran {{ texto(sobrante) }} {{ form.unidad.trim() }}</span>
                    que no completan una medida y se perderán del inventario.
                </p>
                <label class="flex min-h-[44px] items-center gap-2 font-semibold">
                    <input type="checkbox" v-model="form.acepta_perdida" class="h-5 w-5 rounded border-gray-400 text-indigo-600 focus:ring-indigo-500" />
                    Acepto la pérdida del producto restante
                </label>
                <InputError :message="form.errors.acepta_perdida" />
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div v-for="tipo in TIPOS_PRECIO" :key="tipo.campo" class="ui-field">
                    <label :for="`dosificar-${tipo.campo}`" class="ui-label">{{ tipo.label }}</label>
                    <input :id="`dosificar-${tipo.campo}`" type="number" min="0.01" step="0.01" inputmode="decimal"
                        :required="tipo.campo === 'precio_publico'" v-model="form[tipo.campo]" @input="preciosEditados = true" class="ui-input" />
                    <InputError class="mt-1" :message="form.errors[tipo.campo]" />
                </div>
            </div>
            <p class="-mt-2 text-xs text-gray-600 dark:text-gray-400">
                Precios por medida. Se sugiere el precio del envase entre el número de medidas; si la medida ya existe, se actualizan.
            </p>

            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" class="ui-btn ui-btn-lg" @click="closeModal">Cancelar</button>
                <button type="submit" class="ui-btn ui-btn-primary ui-btn-lg" :disabled="!puedeGuardar">
                    {{ form.processing ? 'Dosificando...' : 'Dosificar' }}
                </button>
            </div>
        </form>
    </div>
</template>
