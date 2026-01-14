<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="cardex-title" role="dialog"
        aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="close"></div>

            <div
                class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m-3-9a9 9 0 100 18 9 9 0 000-18z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="cardex-title">
                            Cardex de inventario
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ productName }}
                        </p>

                        <div class="mt-4 space-y-3">
                            <div v-if="errorMessage" class="rounded-md border border-red-200 bg-red-50 p-3 text-red-800">
                                {{ errorMessage }}
                            </div>

                            <div v-else-if="loading"
                                class="rounded-md border border-dashed border-gray-200 bg-gray-50 p-3 text-sm text-gray-600">
                                Cargando movimientos...
                            </div>

                            <div v-else-if="!movimientos.length"
                                class="rounded-md border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
                                No hay movimientos registrados para este producto.
                            </div>

                            <div v-else class="overflow-x-auto max-h-[60vh]">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr class="[&>th]:px-4 [&>th]:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <th>Fecha</th>
                                            <th>Movimiento</th>
                                            <th>Cantidad anterior</th>
                                            <th>Movimiento</th>
                                            <th>Resultado</th>
                                            <th>Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                        <tr v-for="movimiento in movimientos" :key="movimiento.id">
                                            <td class="whitespace-nowrap px-4 py-2 text-gray-900 font-medium">
                                                {{ movimiento.fecha || 'Sin fecha' }}
                                            </td>
                                            <td class="px-4 py-2">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                                                    :class="tipoBadge(movimiento.tipo)">
                                                    {{ movimiento.tipo }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2 text-gray-700">
                                                {{ formatNumber(movimiento.cantidad_actual) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2"
                                                :class="movimiento.cantidad_movida >= 0 ? 'text-green-600' : 'text-red-600'">
                                                {{ formatSigned(movimiento.cantidad_movida) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2 text-gray-900 font-semibold">
                                                {{ formatNumber(movimiento.cantidad_resultante) }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2 text-gray-700">
                                                {{ movimiento.usuario }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded shadow-sm hover:bg-gray-100 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-700"
                                @click="close">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
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

const movimientos = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const lastFetchedId = ref(null);

const productName = computed(() => props.producto?.nombre || 'Producto sin nombre');

const resetState = () => {
    movimientos.value = [];
    loading.value = false;
    errorMessage.value = '';
    lastFetchedId.value = null;
};

const close = () => {
    resetState();
    emit('close');
};

const fetchCardex = async () => {
    if (!props.producto?.id) {
        errorMessage.value = 'No se encontró el producto seleccionado.';
        return;
    }

    const productoId = props.producto.id;
    loading.value = true;
    errorMessage.value = '';
    lastFetchedId.value = productoId;
    try {
        const { data } = await axios.get(route('inventario.cardex', productoId));
        movimientos.value = data?.movimientos ?? [];
    } catch (error) {
        const message = error?.response?.data?.message ?? 'No se pudo cargar el cardex.';
        errorMessage.value = message;
        notify(message, 'error');
        lastFetchedId.value = null;
    } finally {
        loading.value = false;
    }
};

watch(() => props.show, (visible) => {
    if (visible) {
        fetchCardex();
    } else {
        resetState();
    }
});

watch(() => props.producto?.id, (id) => {
    if (props.show && id && id !== lastFetchedId.value) {
        fetchCardex();
    }
});

const formatNumber = (value) => Number(value ?? 0).toFixed(2);

const formatSigned = (value) => {
    const num = Number(value ?? 0);
    const formatted = Math.abs(num).toFixed(2);
    if (num > 0) return `+${formatted}`;
    if (num < 0) return `-${formatted}`;
    return formatted;
};

const tipoBadge = (tipo) => {
    const variants = {
        'Alta de inventario': 'bg-green-100 text-green-700',
        'Reseteo a cero': 'bg-amber-100 text-amber-700',
        Venta: 'bg-red-100 text-red-700',
        Entrada: 'bg-green-100 text-green-700',
        Salida: 'bg-red-100 text-red-700',
        Creación: 'bg-blue-100 text-blue-700',
        Ajuste: 'bg-amber-100 text-amber-700',
    };
    return variants[tipo] ?? 'bg-gray-100 text-gray-700';
};
</script>
