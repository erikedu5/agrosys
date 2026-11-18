<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
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

const emit = defineEmits(['close', 'success']);

const cantidadInput = ref(null);

const form = useForm({
    cantidad: '',
    id: null,
});

const stockActual = computed(() => Number(props.producto?.cantidad) || 0);
const cantidadAgregar = computed(() => {
    const numero = Number(form.cantidad);
    return form.cantidad !== '' && Number.isFinite(numero) && numero > 0 ? numero : 0;
});
const stockNuevo = computed(() => Math.round((stockActual.value + cantidadAgregar.value) * 100) / 100);
const puedeGuardar = computed(() => cantidadAgregar.value > 0 && !form.processing);

watch(() => props.show, (value) => {
    if (!value) {
        return;
    }
    form.clearErrors();
    form.cantidad = '';
    form.id = props.producto?.id || null;
    nextTick(() => cantidadInput.value?.focus());
});

const closeModal = () => {
    form.clearErrors();
    form.reset();
    emit('close');
};

const submit = () => {
    if (!props.producto?.id || !puedeGuardar.value) {
        return;
    }
    const cantidad = cantidadAgregar.value;
    const nombre = props.producto.nombre;
    form.id = props.producto.id;
    form.post(route('inventario.addInventario'), {
        preserveScroll: true,
        onSuccess: () => {
            notify(`Se agregaron ${cantidad} unidades a "${nombre}".`, 'success');
            emit('success');
            closeModal();
        },
        onError: (errors) => {
            ['id', 'bloqueo', 'producto'].forEach((campo) => {
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
        <form role="dialog" aria-modal="true" aria-labelledby="modal-add-inventario-title" @submit.prevent="submit" @keydown.esc="closeModal"
            class="flex max-h-[92vh] w-full flex-col gap-4 overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:max-w-md sm:rounded-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="modal-add-inventario-title" class="text-xl font-bold text-gray-900 dark:text-gray-100">Agregar al inventario</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold">{{ producto?.nombre || 'Producto sin nombre' }}</span>
                        <template v-if="producto?.tamano"> ({{ producto.tamano }})</template>
                    </p>
                </div>
                <button type="button" @click="closeModal" aria-label="Cerrar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-900">
                <p class="ui-muted">Actualmente en existencia</p>
                <p class="text-4xl font-extrabold" :class="stockActual > 0 ? 'text-gray-900 dark:text-gray-100' : 'text-red-600 dark:text-red-400'">
                    {{ stockActual }}
                </p>
            </div>

            <div class="ui-field">
                <label for="add-inventario-cantidad" class="ui-label">Cantidad a agregar</label>
                <input id="add-inventario-cantidad" ref="cantidadInput" type="number" min="0.01" step="0.01" inputmode="decimal" required
                    v-model="form.cantidad" placeholder="0" class="ui-input text-right text-2xl font-bold" />
                <InputError class="mt-1" :message="form.errors.cantidad" />
            </div>

            <p v-if="cantidadAgregar > 0" class="ui-alert ui-alert-blue" role="status">
                <span>Nueva existencia: <span class="font-bold">{{ stockNuevo }}</span></span>
            </p>

            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" class="ui-btn ui-btn-lg" @click="closeModal">Cancelar</button>
                <button type="submit" class="ui-btn ui-btn-primary ui-btn-lg" :disabled="!puedeGuardar">
                    {{ form.processing ? 'Agregando...' : 'Agregar' }}
                </button>
            </div>
        </form>
    </div>
</template>
