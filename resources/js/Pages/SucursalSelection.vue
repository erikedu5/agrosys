<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    sucursales: Array,
    empresa: String
});

const loading = ref(false);
const selectedSucursal = ref(null);

const selectSucursal = async (sucursal) => {
    if (loading.value) return;
    
    loading.value = true;
    selectedSucursal.value = sucursal.id;

    try {
        const response = await fetch('/sucursal/select', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                sucursal_id: sucursal.id
            })
        });

        const data = await response.json();

        if (data.success) {
            // Redirigir al dashboard
            router.visit('/dashboard');
        } else {
            alert(data.error || 'Error al seleccionar sucursal');
            loading.value = false;
            selectedSucursal.value = null;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error de conexión');
        loading.value = false;
        selectedSucursal.value = null;
    }
};
</script>

<template>
    <AppLayout title="Seleccionar Sucursal">
        <div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 flex items-center justify-center p-4">
            <div class="max-w-4xl w-full">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-green-600 to-blue-600 rounded-full mb-6">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Selecciona tu Sucursal</h1>
                    <h2 class="text-xl text-gray-600 mb-2">{{ empresa }}</h2>
                    <p class="text-gray-500">Elige la sucursal con la que trabajarás en esta sesión</p>
                </div>

                <!-- Grid de sucursales -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="sucursal in sucursales"
                        :key="sucursal.id"
                        @click="selectSucursal(sucursal)"
                        :class="[
                            'relative bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:-translate-y-1 border-2',
                            selectedSucursal === sucursal.id 
                                ? 'border-green-500 bg-green-50' 
                                : 'border-gray-200 hover:border-green-300',
                            loading && selectedSucursal === sucursal.id ? 'opacity-75' : ''
                        ]"
                    >
                        <!-- Badge de matriz -->
                        <div v-if="sucursal.es_matriz" class="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                            Matriz
                        </div>

                        <!-- Contenido de la tarjeta -->
                        <div class="p-6">
                            <!-- Icono y título -->
                            <div class="flex items-center gap-4 mb-4">
                                <div :class="[
                                    'flex items-center justify-center w-14 h-14 rounded-xl',
                                    sucursal.es_matriz 
                                        ? 'bg-gradient-to-r from-yellow-400 to-orange-500' 
                                        : 'bg-gradient-to-r from-green-400 to-blue-500'
                                ]">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">{{ sucursal.nombre }}</h3>
                                    <p v-if="sucursal.es_matriz" class="text-sm text-yellow-600 font-medium">Casa Matriz</p>
                                    <p v-else class="text-sm text-gray-500">Sucursal</p>
                                </div>
                            </div>

                            <!-- Información de contacto -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 text-gray-600">
                                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="text-sm">{{ sucursal.direccion || 'Dirección no disponible' }}</span>
                                </div>
                                
                                <div v-if="sucursal.telefono" class="flex items-center gap-3 text-gray-600">
                                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span class="text-sm">{{ sucursal.telefono }}</span>
                                </div>
                            </div>

                            <!-- Loading indicator -->
                            <div v-if="loading && selectedSucursal === sucursal.id" class="mt-4 flex items-center justify-center">
                                <div class="animate-spin rounded-full h-6 w-6 border-2 border-green-500 border-t-transparent"></div>
                                <span class="ml-2 text-sm text-green-600 font-medium">Seleccionando...</span>
                            </div>

                            <!-- Botón de acción -->
                            <div v-else class="mt-6 text-center">
                                <div class="inline-flex items-center gap-2 text-green-600 font-semibold">
                                    <span>Ingresar a esta sucursal</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-12 text-center">
                    <p class="text-gray-500 text-sm">
                        Podrás cambiar de sucursal en cualquier momento desde el menú principal
                    </p>
                    <div class="mt-4">
                        <form method="POST" action="/logout" class="inline">
                            <input type="hidden" name="_token" :value="$page.props.csrf_token">
                            <button type="submit" class="text-gray-400 hover:text-gray-600 text-sm underline">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
