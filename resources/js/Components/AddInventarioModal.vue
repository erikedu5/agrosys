<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

            <!-- Modal -->
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                        <!-- Icono de inventario -->
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Agregar stock al inventario
                        </h3>
                        <div class="mt-4">
                            <form @submit.prevent="submit">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Producto</label>
                                    <input 
                                        type="text" 
                                        readonly
                                        class="form-input w-full rounded-md shadow-sm bg-gray-100"
                                        :value="producto?.nombre || 'Producto sin nombre'">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Stock actual: 
                                        <span :class="(producto?.cantidad || 0) > 0 ? 'font-bold text-blue-600' : 'font-bold text-red-600'">
                                            {{ producto?.cantidad || 0 }}
                                        </span>
                                        <span v-if="(producto?.cantidad || 0) === 0" class="text-red-500 text-xs ml-2">
                                            (Sin stock disponible)
                                        </span>
                                    </label>
                                </div>

                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Cantidad a agregar <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        min="0.01" 
                                        step="0.01"
                                        class="form-input w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        v-model="form.cantidad"
                                        placeholder="Ingrese la cantidad"
                                        required
                                        ref="cantidadInput">
                                    <InputError class="mt-1" :message="form.errors.cantidad" />
                                </div>

                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-md">
                                    <button 
                                        type="submit"
                                        :disabled="form.processing"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                                hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                                focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                                dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }">
                                        <span v-if="form.processing">Agregando...</span>
                                        <span v-else>Agregar al inventario</span>
                                    </button>
                                    <button 
                                        type="button"
                                        class="px-4 py-2  mr-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                                hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                                focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                                dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                                        @click="closeModal">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    producto: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'success']);

const cantidadInput = ref(null);

const form = useForm({
    cantidad: '',
    id: null
});

// Watch para resetear el form cuando se abre el modal
watch(() => props.show, (newValue) => {
    if (newValue) {
        form.clearErrors();
        form.cantidad = '';
        form.id = props.producto?.id || null;
        
        // Foco en el input cuando se abre
        nextTick(() => {
            if (cantidadInput.value) {
                cantidadInput.value.focus();
            }
        });
    }
});

const submit = () => {
    if (!props.producto?.id) {
        console.error('No hay producto seleccionado');
        return;
    }

    form.id = props.producto.id;
    
    form.post(route('inventario.addInventario'), {
        onSuccess: () => {
            emit('success');
            closeModal();
        },
        onError: (errors) => {
            console.error('Error al agregar inventario:', errors);
        }
    });
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
    emit('close');
};
</script>

<style scoped>
/* Animaciones personalizadas si las necesitas */
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
</style>
