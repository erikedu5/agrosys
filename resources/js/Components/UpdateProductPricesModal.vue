<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 py-8 sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

            <div
                class="inline-block w-full max-w-xl transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0 0V4m0 12v4m8-6a8 8 0 11-16 0 8 8 0 0116 0z" />
                        </svg>
                    </div>
                    <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                            Actualizar precios del producto
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Ajusta los tres precios de venta y, si tienes permiso, el precio de compra.
                        </p>
                    </div>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Producto</label>
                        <input type="text" readonly
                            class="form-input mt-1 w-full rounded-md border-gray-300 bg-gray-100 shadow-sm"
                            :value="producto?.nombre || 'Producto sin nombre'">
                    </div>

                    <div v-if="canManageCosts">
                        <label class="block text-sm font-medium text-gray-700">
                            Precio de compra (opcional)
                        </label>
                        <input type="number" step="0.01" min="0"
                            class="form-input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            v-model="form.precio_unitario">
                        <InputError class="mt-1" :message="form.errors.precio_unitario" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div v-for="(tipo, index) in TIPOS_PRECIO" :key="tipo.campo">
                            <label class="block text-sm font-medium text-gray-700">
                                {{ tipo.label }} <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01"
                                class="form-input mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                v-model="form[tipo.campo]" :ref="(el) => setPrecioInput(el, index)"
                                required>
                            <InputError class="mt-1" :message="form.errors[tipo.campo]" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <button type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @click="closeModal">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="form.processing">
                            <span v-if="form.processing">Guardando...</span>
                            <span v-else>Guardar cambios</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
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
    canManageCosts: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'updated']);

const precioVentaInput = ref(null);
const setPrecioInput = (el, index) => {
    if (index === 0) {
        precioVentaInput.value = el;
    }
};

const form = useForm({
    precio_publico: '',
    precio_preferencial_1: '',
    precio_preferencial_2: '',
    precio_unitario: '',
});

const resetForm = () => {
    form.reset();
    form.clearErrors();

    TIPOS_PRECIO.forEach(({ campo }) => {
        form[campo] = props.producto?.[campo] ?? '';
    });
    form.precio_unitario = props.producto?.precio_unitario ?? '';
};

watch(() => props.show, (value) => {
    if (value) {
        resetForm();
        nextTick(() => {
            if (precioVentaInput.value) {
                precioVentaInput.value.focus();
                if (form.precio_publico !== '' && !Number.isNaN(parseFloat(form.precio_publico))) {
                    precioVentaInput.value.select();
                }
            }
        });
    }
});

const closeModal = () => {
    emit('close');
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (!props.producto?.id) {
        notify('No hay un producto seleccionado para actualizar.', 'error');
        return;
    }

    form.clearErrors();

    form.transform(() => {
        const payload = Object.fromEntries(TIPOS_PRECIO.map(({ campo }) => [campo, form[campo]]));

        if (props.canManageCosts) {
            payload.precio_unitario = form.precio_unitario;
        }

        return payload;
    }).put(route('inventario.updatePrecios', props.producto.id), {
        preserveScroll: true,
        onSuccess: () => {
            notify('Precios actualizados correctamente.', 'success');
            emit('updated', {
                ...Object.fromEntries(TIPOS_PRECIO.map(({ campo }) => [campo, form[campo]])),
                precio_unitario: form.precio_unitario,
            });
            closeModal();
        },
        onError: (errors) => {
            Object.values(errors || {}).forEach((message) => {
                if (message) {
                    notify(message, 'error');
                }
            });
        },
    });
};
</script>
