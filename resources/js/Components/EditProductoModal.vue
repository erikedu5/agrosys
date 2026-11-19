<script setup>
import { nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
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
    clasificaciones: {
        type: Array,
        default: () => [],
    },
    marcas: {
        type: Array,
        default: () => [],
    },
    canManageCosts: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const nombreInput = ref(null);
const clasificacionSeleccionada = ref(null);
const marcaSeleccionada = ref(null);

const form = useForm({
    nombre: '',
    id_clasificacion: 0,
    id_marca: 0,
    precio_unitario: '',
    precio_publico: '',
    precio_preferencial_1: '',
    precio_preferencial_2: '',
    precio_preferencial_3: '',
    tamano: '',
    ingrediente_activo: '',
    barcode: '',
});

watch(() => props.show, (value) => {
    if (!value || !props.producto) {
        return;
    }
    const producto = props.producto;
    form.clearErrors();
    form.nombre = producto.nombre || '';
    form.id_clasificacion = producto.id_clasificacion || 0;
    form.id_marca = producto.id_marca || 0;
    form.precio_unitario = producto.precio_unitario ?? '';
    TIPOS_PRECIO.forEach(({ campo }) => {
        form[campo] = producto[campo] ?? '';
    });
    form.tamano = producto.tamano || '';
    form.ingrediente_activo = producto.ingrediente_activo || '';
    form.barcode = producto.barcode || '';
    clasificacionSeleccionada.value = props.clasificaciones.find((c) => c.id === producto.id_clasificacion) || null;
    marcaSeleccionada.value = props.marcas.find((m) => m.id === producto.id_marca) || null;
    nextTick(() => nombreInput.value?.focus());
});

watch(clasificacionSeleccionada, (v) => {
    form.id_clasificacion = v ? v.id : 0;
});

watch(marcaSeleccionada, (v) => {
    form.id_marca = v ? v.id : 0;
});

const validar = () => {
    form.clearErrors();
    const errores = {};
    if (!form.nombre?.trim()) {
        errores.nombre = 'El nombre del producto es requerido';
    }
    if (!form.id_clasificacion) {
        errores.id_clasificacion = 'Debe seleccionar una clasificación';
    }
    if (!form.id_marca) {
        errores.id_marca = 'Debe seleccionar una marca';
    }
    if (props.canManageCosts && form.precio_unitario !== '' && form.precio_unitario !== null && parseFloat(form.precio_unitario) < 0) {
        errores.precio_unitario = 'El precio de compra no puede ser negativo';
    }
    TIPOS_PRECIO.forEach(({ campo, label }) => {
        if (!form[campo] || parseFloat(form[campo]) <= 0) {
            errores[campo] = `El precio ${label.toLowerCase()} debe ser mayor a 0`;
        }
    });
    if (!form.tamano?.trim()) {
        errores.tamano = 'El tamaño del producto es requerido';
    }
    form.setError(errores);
    return Object.keys(errores).length === 0;
};

const closeModal = () => {
    emit('close');
};

const submit = () => {
    if (!props.producto?.id || form.processing || !validar()) {
        return;
    }
    form.put(route('inventario.update', props.producto.id), {
        preserveScroll: true,
        onSuccess: () => {
            notify('Producto actualizado correctamente.', 'success');
            closeModal();
        },
        onError: (errors) => {
            Object.values(errors || {}).forEach((message) => {
                if (typeof message === 'string' && message.length) {
                    notify(message, 'error');
                }
            });
        },
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[60] flex items-end justify-center bg-gray-900/55 sm:items-center sm:p-6" @click.self="closeModal">
        <form role="dialog" aria-modal="true" aria-labelledby="modal-editar-producto-title" @submit.prevent="submit" @keydown.esc="closeModal"
            class="flex max-h-[92vh] w-full flex-col gap-4 overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:max-w-2xl sm:rounded-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="modal-editar-producto-title" class="text-xl font-bold text-gray-900 dark:text-gray-100">Editar producto</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Datos, precios y código</p>
                </div>
                <button type="button" @click="closeModal" aria-label="Cerrar" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <section class="flex flex-col gap-4">
                <h3 class="ui-section-title">Producto</h3>
                <div class="ui-field">
                    <label for="editar-nombre" class="ui-label">Nombre *</label>
                    <input id="editar-nombre" ref="nombreInput" type="text" class="ui-input" :class="{ 'ui-input-error': form.errors.nombre }"
                        v-model="form.nombre" @blur="form.nombre && form.clearErrors('nombre')" required>
                    <InputError :message="form.errors.nombre" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="ui-field">
                        <label class="ui-label">Clasificación *</label>
                        <vue-single-select v-model="clasificacionSeleccionada" :options="clasificaciones" option-key="id"
                            option-label="nombre" placeholder="Seleccione una clasificación" class="w-full" />
                        <InputError :message="form.errors.id_clasificacion" />
                    </div>
                    <div class="ui-field">
                        <label class="ui-label">Marca *</label>
                        <vue-single-select v-model="marcaSeleccionada" :options="marcas" option-key="id"
                            option-label="nombre" placeholder="Seleccione una marca" class="w-full" />
                        <InputError :message="form.errors.id_marca" />
                    </div>
                </div>

                <div class="ui-field">
                    <label for="editar-tamano" class="ui-label">Tamaño *</label>
                    <input id="editar-tamano" type="text" class="ui-input" :class="{ 'ui-input-error': form.errors.tamano }"
                        v-model="form.tamano" @blur="form.tamano && form.clearErrors('tamano')" required>
                    <p class="ui-help">Agrega la unidad al tamaño, por ejemplo: “kg”, “g”, “ml”, “l”.</p>
                    <InputError :message="form.errors.tamano" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="ui-field">
                        <label for="editar-ingrediente" class="ui-label">Ingrediente activo</label>
                        <input id="editar-ingrediente" type="text" class="ui-input" v-model="form.ingrediente_activo">
                        <InputError :message="form.errors.ingrediente_activo" />
                    </div>
                    <div class="ui-field">
                        <label for="editar-barcode" class="ui-label">Código de barras</label>
                        <input id="editar-barcode" type="text" class="ui-input" v-model="form.barcode">
                        <InputError :message="form.errors.barcode" />
                    </div>
                </div>
            </section>

            <section class="flex flex-col gap-4">
                <h3 class="ui-section-title">Precios</h3>
                <div v-if="canManageCosts" class="ui-field">
                    <label for="editar-precio-unitario" class="ui-label">Precio de compra (opcional)</label>
                    <input id="editar-precio-unitario" type="number" step="0.01" min="0" inputmode="decimal" class="ui-input"
                        :class="{ 'ui-input-error': form.errors.precio_unitario }" v-model="form.precio_unitario">
                    <InputError :message="form.errors.precio_unitario" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="tipo in TIPOS_PRECIO" :key="tipo.campo" class="ui-field">
                        <label :for="`editar-${tipo.campo}`" class="ui-label">Precio {{ tipo.label }} *</label>
                        <input :id="`editar-${tipo.campo}`" type="number" step="0.01" min="0.01" inputmode="decimal" class="ui-input"
                            :class="{ 'ui-input-error': form.errors[tipo.campo] }" v-model="form[tipo.campo]" required>
                        <InputError :message="form.errors[tipo.campo]" />
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" class="ui-btn ui-btn-lg" @click="closeModal">Cancelar</button>
                <button type="submit" class="ui-btn ui-btn-primary ui-btn-lg" :disabled="form.processing">
                    {{ form.processing ? 'Guardando...' : 'Actualizar producto' }}
                </button>
            </div>
        </form>
    </div>
</template>
