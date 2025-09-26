<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch, defineProps, onMounted } from 'vue';
import { usePersistedForm } from '@/stores/formStore';
import Pagination from '@/Components/Pagination.vue'
import VueSingleSelect from '@/Components/VueSingleSelect.vue';
import InputError from '@/Components/InputError.vue';
import AddInventarioModal from '@/Components/AddInventarioModal.vue';

const props = defineProps({
    producto: Object,
    enfermedadesFlor: Array,
    solucion: Object,
    solucionesByProduct: Array,
});

// Modal state
const showInventarioModal = ref(false);

const { form, reset } = usePersistedForm('agregarSolucionForm', {
    id_producto: props.solucion != null ? props.solucion.id_producto : props.producto.id,
    id_enfermedad_tipo_flor: props.solucion != null ? props.solucion.id_enfermedad_tipo_flor : 0,
    dosis_bomba_ml: props.solucion != null ? props.solucion.dosis_bomba_ml : 0,
    dosis_tambo_ml: props.solucion != null ? props.solucion.dosis_tambo_ml : 0,
    condiciones: props.solucion != null ? props.solucion.condiciones : '',
    id: props.solucion != null ? props.solucion.id : null,
});

// Detectar si viene de la creación de un producto
onMounted(() => {
    // Verificar si hay un parámetro que indique que viene de producto recién creado
    const urlParams = new URLSearchParams(window.location.search);
    const fromProductCreation = urlParams.get('from_product_creation');

    if (fromProductCreation === 'true') {
        showInventarioModal.value = true;
        // Limpiar el parámetro de la URL sin recargar la página
        window.history.replaceState({}, document.title, window.location.pathname + '?id_producto=' + props.producto.id);
    }
});

const submit = async () => {
    if (props.solucion == undefined) {
        form.post(route('solucion.store'), {
            onSuccess: reset,
        });
    } else {
        form.put(route('solucion.update', props.solucion.id), {
            onSuccess: reset,
        });
    }
}



// Funciones del modal de inventario
const openInventarioModal = () => {
    showInventarioModal.value = true;
};

const closeInventarioModal = () => {
    showInventarioModal.value = false;
};

const onInventarioSuccess = () => {
    // Recargar la página para actualizar el stock del producto
    window.location.reload();
};


</script>

<template>
    <AppLayout title="CrearProducto">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Crear solucion para el producto: {{ props.producto.nombre }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Stock actual:
                        <span
                            :class="(props.producto.cantidad || 0) > 0 ? 'font-bold text-blue-600' : 'font-bold text-red-600'">
                            {{ props.producto.cantidad || 0 }}
                        </span>
                        unidades
                        <span v-if="(props.producto.cantidad || 0) === 0" class="text-red-500 text-xs ml-2">
                            (Sin stock disponible)
                        </span>
                    </p>
                </div>
                <button @click="openInventarioModal"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Agregar Inventario
                </button>
            </div>
        </template>

        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grow">

                <div class="md-col-span-2 mt-5 md:mt-0">

                    <div class="shadow bg-white md:rounded-md p-4">
                        <label class="block font-medium text-sm text-gray-700">Enfermedad y flor que afecta</label>
                        <vue-single-select id="solucion" placeholder="Seleccione enfermedad"
                            v-model="form.id_enfermedad_tipo_flor" option-key="id" option-label="nombre"
                            :options="enfermedadesFlor">
                        </vue-single-select>
                        <InputError class="mt-2" :message="form.errors.id_enfermedad_tipo_flor" />
                        <br>

                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Condiciones de aplicación</label>
                            <textarea class="form-input w-full rounded-md shadow-sm"
                                v-model="form.condiciones"></textarea>
                            <InputError class="mt-2" :message="form.errors.condiciones" />
                            <br>
                            <br>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-sm text-gray-700">Dosis por bomba en
                                        Ml/Gr.</label>
                                    <input type="number" step="0.01" min="0"
                                        class="form-input w-full rounded-md shadow-sm" v-model="form.dosis_bomba_ml"
                                        placeholder="Ej: 25.5">
                                    <InputError class="mt-2" :message="form.errors.dosis_bomba_ml" />
                                    <p class="text-xs text-gray-500 mt-1">Dosis recomendada por bomba de 20 litros</p>
                                </div>

                                <div>
                                    <label class="block font-medium text-sm text-gray-700">Dosis por tambo en
                                        Ml/Gr.</label>
                                    <input type="number" step="0.01" min="0"
                                        class="form-input w-full rounded-md shadow-sm" v-model="form.dosis_tambo_ml"
                                        placeholder="Ej: 375">
                                    <InputError class="mt-2" :message="form.errors.dosis_tambo_ml" />
                                    <p class="text-xs text-gray-500 mt-1">Dosis recomendada por tambo de 300 litros</p>
                                </div>
                            </div>

                            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <p class="text-sm text-blue-700">
                                    <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    Los campos de dosis son independientes. Ingrese cada valor según las recomendaciones
                                    específicas del producto.
                                </p>
                            </div>
                            <br>
                            <br>

                            <button
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                       hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                       focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                       dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                Guardar
                            </button>
                        </form>

                        <hr class="my-6">

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead
                                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th>Id</th>
                                        <th>Nombre del producto</th>
                                        <th>Enfermedad en planta</th>
                                        <th>Dosis en ml por bomba</th>
                                        <th>Dosis en ml por tambo</th>
                                        <th>Condiciones de aplicación</th>
                                        <th>Ultima actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="solucionByProduct in solucionesByProduct.data"
                                        :value="solucionByProduct.nombre" :key="solucionByProduct.id">
                                        <td class="px-4 py-2"> {{ solucionByProduct.id }}</td>
                                        <td class="px-4 py-2"> {{ props.producto.nombre }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.enfermedad.nombre }} en {{
                                            solucionByProduct.tipoFlor.nombre }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.dosis_bomba_ml }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.dosis_tambo_ml }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.condiciones }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.updated_at }} </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div name="Pagination">

                                <Pagination class="mt-6" :links="solucionesByProduct.links"
                                    :prefix="'&id_producto=' + props.producto.id" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de agregar inventario -->
        <AddInventarioModal :show="showInventarioModal" :producto="props.producto" @close="closeInventarioModal"
            @success="onInventarioSuccess" />
    </AppLayout>
</template>
