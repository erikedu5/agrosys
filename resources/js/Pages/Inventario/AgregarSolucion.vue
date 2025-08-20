
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import { ref, watch, defineProps } from 'vue';
    import { router, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue'
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';
    import InputError from '@/Components/InputError.vue';

    const props = defineProps({
        producto: Object,
        enfermedadesFlor: Array,
        solucion: Object,
        solucionesByProduct: Array,
    });

    const form = useForm({
        id_producto: props.solucion != null ? props.solucion.id_producto : props.producto.id,
        id_enfermedad_tipo_flor: props.solucion != null ? props.solucion.id_enfermedad_tipo_flor : 0,
        dosis_bomba_ml: props.solucion != null ? props.solucion.dosis_bomba_ml: 0,
        dosis_tambo_ml: props.solucion != null ? props.solucion.dosis_tambo_ml: 0,
        condiciones: props.solucion != null ? props.solucion.condiciones: '',
        id: props.solucion != null ? props.solucion.id: null,
    });

    const submit = async() => {
        if (props.solucion == undefined) {
            form.post(route('solucion.store'), form);
        } else {
            form.put(route('solucion.update', props.solucion.id), form);
        }
        form.reset();
    }

    const addDosisbomba = () => {
        form.dosis_tambo_ml = form.dosis_bomba_ml * 15;
    }

    const addDosistambo = () => {
        form.dosis_bomba_ml = form.dosis_tambo_ml / 15;
    }


</script>

<template>
    <AppLayout title="CrearProducto">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Crear solucion para el producto: {{ props.producto.nombre }}
            </h2>
        </template>

        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grow">

                <div class="md-col-span-2 mt-5 md:mt-0">

                    <div class="shadow bg-white md:rounded-md p-4">
                        <label class="block font-medium text-sm text-gray-700">Enfermedad y flor que afecta</label>
        <vue-single-select
            id = "solucion"
            placeholder="Selecione enfermedad"
            v-model="form.id_enfermedad_tipo_flor"
            option-key="id"
            option-label="nombre"
            :class="selectClient ? 'pointer-events-none': '' "
            :options="enfermedadesFlor">
        </vue-single-select>
        <InputError class="mt-2" :message="form.errors.id_enfermedad_tipo_flor" />
                        <br>

                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Condiciones de aplicación</label>
                            <textarea
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.condiciones"></textarea>
                            <InputError class="mt-2" :message="form.errors.condiciones" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Dosis por bomba en Ml/Gr.</label>
                            <input type="number" step="0.01" @input="addDosisbomba()"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.dosis_bomba_ml">
                            <InputError class="mt-2" :message="form.errors.dosis_bomba_ml" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Dosis por tambo en Ml/Gr.</label>
                            <input type="number" step="0.01" @input="addDosistambo()"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.dosis_tambo_ml">
                            <InputError class="mt-2" :message="form.errors.dosis_tambo_ml" />
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
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
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
                                    <tr v-for="solucionByProduct in solucionesByProduct.data" :value="solucionByProduct.nombre" :key=" solucionByProduct.id ">
                                        <td class="px-4 py-2"> {{ solucionByProduct.id }}</td>
                                        <td class="px-4 py-2"> {{ props.producto.nombre }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.enfermedad.nombre }} en {{ solucionByProduct.tipoFlor.nombre }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.dosis_bomba_ml }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.dosis_tambo_ml }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.condiciones }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.updated_at }} </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div name="Pagination">

                                <Pagination class="mt-6" :links="solucionesByProduct.links" :prefix="'&id_producto=' + props.producto.id" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
