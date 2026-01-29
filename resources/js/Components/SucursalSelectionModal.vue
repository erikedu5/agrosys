<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                Seleccionar Sucursal
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Sucursal actual: <span class="font-medium text-green-600">{{ sucursalActiva?.nombre }}</span>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Selecciona la sucursal con la que deseas trabajar:
                                </p>
                                <!-- Debug info -->
                                <p class="text-xs text-gray-400 mt-1">
                                    Debug: {{ sucursalActiva?.sucursalesDisponibles?.length || 0 }} sucursales disponibles
                                </p>
                            </div>
                            
                            <!-- Lista de sucursales -->
                            <div class="mt-4 space-y-2 max-h-60 overflow-y-auto">
                                <template v-for="sucursal in sucursalActiva?.sucursalesDisponibles" :key="sucursal.id">
                                    <button
                                        v-if="sucursal.id !== sucursalActiva?.id"
                                        @click="seleccionarSucursal(sucursal.id)"
                                        :disabled="loading"
                                        class="w-full flex items-center justify-between p-3 text-left border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ sucursal.nombre }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ sucursal.direccion || 'Sin dirección' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones -->
                                <!-- Botones -->
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        @click="$emit('close')"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:w-auto sm:text-sm"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    sucursalActiva: Object
});

const emit = defineEmits(['close']);

const loading = ref(false);

// Debug: observar cambios en las props
watch(() => props.show, (newValue) => {
    console.log('Modal show prop cambió a:', newValue);
});

watch(() => props.sucursalActiva, (newValue) => {
    console.log('SucursalActiva prop:', newValue);
}, { immediate: true });

const seleccionarSucursal = async (sucursalId) => {
    if (loading.value) return;
    
    loading.value = true;
    console.log('Cambiando a sucursal:', sucursalId);
    
    try {
        // Guardar en localStorage
        localStorage.setItem('selected_sucursal_id', sucursalId);

        router.post('/sucursal/change', {
            sucursal_id: sucursalId
        }, {
            onSuccess: () => {
                console.log('Cambio de sucursal exitoso');
                emit('close');
                loading.value = false;
            },
            onError: (errors) => {
                console.error('Error al cambiar sucursal:', errors);
                alert('Error al cambiar sucursal');
                loading.value = false;
            }
        });
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
        loading.value = false;
    }
};
</script>
