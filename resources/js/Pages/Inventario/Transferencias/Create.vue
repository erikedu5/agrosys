<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    sucursalesDestino: Array,
    sucursalOrigen: Object
});

const form = useForm({
    id_sucursal_destino: '',
    productos: [],
    observaciones: ''
});

const searchTerm = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

const searchProducts = async () => {
    if (searchTerm.value.length < 2) {
        searchResults.value = [];
        return;
    }
    
    isSearching.value = true;
    try {
        // Usamos la nueva ruta web
        const response = await axios.get(route('transferencias.buscarProductos'), {
            params: { busqueda: searchTerm.value }
        });
        searchResults.value = response.data;
    } catch (error) {
        console.error("Error buscando productos", error);
    } finally {
        isSearching.value = false;
    }
};

const addProduct = (producto) => {
    const existing = form.productos.find(p => p.id === producto.id);
    if (!existing) {
        form.productos.push({
            id: producto.id,
            nombre: producto.nombre,
            cantidad_disponible: parseFloat(producto.cantidad), // Stock actual
            cantidad: 1 // Default
        });
    }
    searchTerm.value = '';
    searchResults.value = [];
};

const removeProduct = (index) => {
    form.productos.splice(index, 1);
};

const submit = () => {
    form.post(route('transferencias.store'));
};
</script>

<template>
    <AppLayout title="Nueva Transferencia">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nueva Transferencia
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Sucursal Origen</label>
                            <div class="mt-1 p-2 bg-gray-100 rounded-md">
                                {{ sucursalOrigen.nombre }} (Actual)
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="destino" class="block text-sm font-medium text-gray-700">Sucursal Destino</label>
                            <select id="destino" v-model="form.id_sucursal_destino" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" required>
                                <option value="" disabled>Seleccione una sucursal</option>
                                <option v-for="suc in sucursalesDestino" :key="suc.id" :value="suc.id">
                                    {{ suc.nombre }}
                                </option>
                            </select>
                            <div v-if="form.errors.id_sucursal_destino" class="text-red-500 text-xs mt-1">{{ form.errors.id_sucursal_destino }}</div>
                        </div>

                        <div class="mb-6 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Productos a Transferir</h3>
                            
                            <!-- Buscador -->
                            <div class="relative mb-4">
                                <label class="block text-sm font-medium text-gray-700">Buscar Producto (Nombre o Código)</label>
                                <div class="flex gap-2">
                                    <input type="text" v-model="searchTerm" @keyup.enter="searchProducts" placeholder="Escriba para buscar..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <button type="button" @click="searchProducts" class="mt-1 bg-gray-200 px-4 py-2 rounded-md hover:bg-gray-300">Buscar</button>
                                </div>
                                
                                <!-- Resultados Búsqueda -->
                                <div v-if="searchResults.length > 0" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto sm:text-sm">
                                    <div v-for="res in searchResults" :key="res.id" @click="addProduct(res)" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50">
                                        <div class="flex justify-between">
                                            <span class="font-bold">{{ res.nombre }}</span>
                                            <span class="text-gray-500 text-xs">Stock: {{ res.cantidad }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lista Productos Seleccionados -->
                            <div v-if="form.productos.length > 0" class="space-y-4">
                                <div v-for="(prod, index) in form.productos" :key="prod.id" class="flex items-center gap-4 bg-gray-50 p-4 rounded-md border text-sm">
                                    <div class="flex-1">
                                        <p class="font-bold text-gray-900">{{ prod.nombre }}</p>
                                        <p class="text-xs text-gray-500">Disponible: {{ prod.cantidad_disponible }}</p>
                                    </div>
                                    <div class="w-32">
                                        <label class="block text-xs font-medium text-gray-700">Cantidad</label>
                                        <input type="number" v-model="prod.cantidad" min="0.01" step="0.01" :max="prod.cantidad_disponible" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm h-8">
                                    </div>
                                    <button type="button" @click="removeProduct(index)" class="text-red-600 hover:text-red-900 font-bold p-2">✕</button>
                                </div>
                                <div v-if="form.errors['productos']" class="text-red-500 text-xs">{{ form.errors['productos'] }}</div>
                            </div>
                             <div v-else class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                                No has agregado productos a la transferencia. Usa el buscador arriba.
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Notas Adicionales</label>
                            <textarea v-model="form.observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <div class="flex justify-end gap-4">
                            <Link :href="route('transferencias.index')" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancelar
                            </Link>
                            <div class="mb-4 text-xs text-gray-500 text-right">
                                * La transferencia quedará pendiente hasta que la sucursal receptora la acepte.
                            </div>
                            <button type="submit" :disabled="form.processing || form.productos.length === 0" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                Enviar Transferencia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
