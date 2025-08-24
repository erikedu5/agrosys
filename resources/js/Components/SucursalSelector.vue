<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    sucursalActiva: Object
});

const showDropdown = ref(false);
const loading = ref(false);

const cambiarSucursal = async (sucursalId) => {
    if (loading.value) return;
    
    loading.value = true;
    showDropdown.value = false;

    try {
        const response = await fetch('/sucursal/change', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                sucursal_id: sucursalId
            })
        });

        const data = await response.json();

        if (data.success) {
            // Recargar la página para aplicar los cambios
            window.location.reload();
        } else {
            alert(data.error || 'Error al cambiar sucursal');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div v-if="sucursalActiva" class="relative">
        <!-- Mostrar sucursal actual -->
        <div class="flex items-center gap-2 text-sm">
            <div class="flex items-center gap-2 px-3 py-1 bg-green-100 rounded-full border border-green-200">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span class="font-medium text-green-700">{{ sucursalActiva.nombre }}</span>
                
                <!-- Botón para cambiar sucursal (solo admin empresa) -->
                <button 
                    v-if="sucursalActiva.esAdminEmpresa && sucursalActiva.sucursalesDisponibles.length > 1"
                    @click="showDropdown = !showDropdown"
                    class="text-green-600 hover:text-green-800 transition-colors"
                    :disabled="loading"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Dropdown para cambiar sucursal -->
        <div 
            v-if="showDropdown && sucursalActiva.esAdminEmpresa" 
            class="absolute top-full right-0 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
            @click.stop
        >
            <div class="p-3 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-700">Cambiar a:</p>
            </div>
            <div class="max-h-60 overflow-y-auto">
                <button
                    v-for="sucursal in sucursalActiva.sucursalesDisponibles"
                    :key="sucursal.id"
                    @click="cambiarSucursal(sucursal.id)"
                    :disabled="sucursal.id === sucursalActiva.id || loading"
                    :class="[
                        'w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors flex items-center gap-3',
                        sucursal.id === sucursalActiva.id 
                            ? 'bg-green-50 text-green-700 cursor-default' 
                            : 'text-gray-700 hover:text-gray-900'
                    ]"
                >
                    <div class="flex items-center gap-2 flex-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <div>
                            <div class="font-medium">{{ sucursal.nombre }}</div>
                            <div v-if="sucursal.direccion" class="text-xs text-gray-500">{{ sucursal.direccion }}</div>
                        </div>
                    </div>
                    
                    <!-- Badge matriz -->
                    <span v-if="sucursal.es_matriz" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        Matriz
                    </span>
                    
                    <!-- Indicador de sucursal actual -->
                    <svg 
                        v-if="sucursal.id === sucursalActiva.id" 
                        class="w-4 h-4 text-green-600" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Loading indicator -->
            <div v-if="loading" class="p-3 border-t border-gray-100 flex items-center justify-center gap-2">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-green-500 border-t-transparent"></div>
                <span class="text-sm text-gray-600">Cambiando sucursal...</span>
            </div>
        </div>

        <!-- Overlay para cerrar dropdown -->
        <div 
            v-if="showDropdown" 
            class="fixed inset-0 z-40" 
            @click="showDropdown = false"
        ></div>
    </div>
</template>

<style scoped>
/* Asegurar que el dropdown aparezca correctamente */
.relative {
    position: relative;
}
</style>
