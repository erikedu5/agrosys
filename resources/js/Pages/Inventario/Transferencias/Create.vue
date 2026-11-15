<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { Link } from '@inertiajs/vue3';
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
            <PageHeader title="Nueva transferencia" :subtitle="`Desde ${sucursalOrigen.nombre}`" :back="route('transferencias.index')" />
        </template>

        <div class="ui-page ui-page-narrow">
            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="ui-section-title">Sucursales</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="ui-field">
                            <span class="ui-label">Sucursal origen</span>
                            <div class="flex min-h-12 items-center rounded-xl border border-gray-200 bg-gray-50 px-4 font-semibold dark:border-gray-700 dark:bg-gray-900">{{ sucursalOrigen.nombre }} (actual)</div>
                        </div>
                        <div class="ui-field">
                            <label for="destino" class="ui-label">Sucursal destino</label>
                            <select id="destino" v-model="form.id_sucursal_destino" class="ui-input" required>
                                <option value="" disabled>Seleccione una sucursal</option>
                                <option v-for="suc in sucursalesDestino" :key="suc.id" :value="suc.id">{{ suc.nombre }}</option>
                            </select>
                            <p v-if="form.errors.id_sucursal_destino" class="text-sm text-red-600">{{ form.errors.id_sucursal_destino }}</p>
                        </div>
                    </div>
                </section>

                <section class="ui-card flex flex-col gap-4 p-4 sm:p-5">
                    <h2 class="ui-section-title">Productos a transferir</h2>

                    <div class="relative">
                        <div class="ui-field">
                            <label class="ui-label" for="buscar-producto">Buscar producto (nombre o código)</label>
                            <div class="flex gap-2">
                                <input id="buscar-producto" type="search" autocomplete="off" v-model="searchTerm" @keydown.enter.prevent="searchProducts" placeholder="Escriba para buscar..." class="ui-input">
                                <button type="button" @click="searchProducts" class="ui-btn h-12" :disabled="isSearching">{{ isSearching ? 'Buscando…' : 'Buscar' }}</button>
                            </div>
                        </div>

                        <div v-if="searchResults.length > 0" class="absolute z-10 mt-1 flex max-h-60 w-full flex-col overflow-auto rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                            <button v-for="res in searchResults" :key="res.id" type="button" @click="addProduct(res)"
                                class="flex min-h-[52px] items-center justify-between gap-3 border-t border-gray-100 px-4 py-2 text-left first:border-t-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700">
                                <span class="font-semibold">{{ res.nombre }}</span>
                                <span class="ui-badge">{{ res.cantidad }} en exist.</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="form.productos.length > 0" class="flex flex-col gap-2">
                        <div v-for="(prod, index) in form.productos" :key="prod.id" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 dark:border-gray-700 dark:bg-gray-900">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ prod.nombre }}</p>
                                <p class="text-[13px] text-gray-600 dark:text-gray-400">Disponible: {{ prod.cantidad_disponible }}</p>
                            </div>
                            <label class="sr-only" :for="`cantidad-${prod.id}`">Cantidad de {{ prod.nombre }}</label>
                            <input :id="`cantidad-${prod.id}`" type="number" v-model="prod.cantidad" min="0.01" step="0.01" :max="prod.cantidad_disponible"
                                class="h-11 w-24 rounded-lg border-gray-300 text-center text-lg font-bold text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <button type="button" @click="removeProduct(index)" :aria-label="`Quitar ${prod.nombre}`"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-red-700 dark:hover:bg-gray-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors['productos']" class="text-sm text-red-600">{{ form.errors['productos'] }}</p>
                    </div>
                    <p v-else class="rounded-xl border-2 border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-600 dark:border-gray-600 dark:text-gray-400">
                        Aún no has agregado productos. Usa el buscador de arriba.
                    </p>
                </section>

                <section class="ui-card p-4 sm:p-5">
                    <div class="ui-field">
                        <label class="ui-label">Notas adicionales</label>
                        <textarea v-model="form.observaciones" rows="3" class="ui-input"></textarea>
                    </div>
                </section>

                <p class="ui-help">La transferencia quedará pendiente hasta que la sucursal receptora la acepte.</p>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Link :href="route('transferencias.index')" class="ui-btn ui-btn-lg">Cancelar</Link>
                    <button type="submit" :disabled="form.processing || form.productos.length === 0" class="ui-btn ui-btn-primary ui-btn-lg">Enviar transferencia</button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
