
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import { ref, watch, defineProps } from 'vue';
    import { router, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue'
    import VueSingleSelect from '@/Components/VueSingleSelect.vue';

    const props = defineProps({
        producto: Object, 
        enfermedadesFlor: Array,
        solucion: Object,
        solucionesByProduct: Array,
        errors: Array,
    });
    
    const form = useForm({
        id_producto: props.solucion != null ? props.solucion.id_producto : props.producto.id,
        id_enfermedad_tipo_flor: props.solucion != null ? props.solucion.id_enfermedad_tipo_flor : 0,
        dosis_bomba_ml: props.solucion != null ? props.solucion.dosis_bomba_ml: 0,
        dosis_tambo_ml: props.solucion != null ? props.solucion.dosis_tambo_ml: 0,
        id: props.solucion != null ? props.solucion.id: null,
    });

    const submit = async() => {
        console.log(form);
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

</script>

<template>
    <AppLayout title="CrearProducto">  
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Crear solucion para el producto: {{ props.producto.nombre }}
            </h2>
        </template>

        <div class="flex">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">

                <div class="md-col-span-2 mt-5 md:mt-0">

                    <div v-if="errors">
                        <div v-for="(v, k) in errors" :key="k" 
                            class="bg-red-400 text-white rounded font-bold mb-4 shadow-lg py-2 px-4 pr-0">
                                {{ v }}
                        </div>
                    </div>

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
                        
                        
                        <!--select v-model="q" id="id_enfermedad_tipo_flor" name="id_enfermedad_tipo_flor"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm 
                                focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="" disabled>Selecione</option>
                            <option v-for="enfermedadFlor in enfermedadesFlor" :value="enfermedadFlor.id" :key="enfermedadFlor.id">
                               
                            </option>
                        </select-->
                            <br>
                            <br>

                            
                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Dosis por bomba en Ml.</label>
                            <input type="number" @input="addDosisbomba()"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.dosis_bomba_ml">
                                <br>
                                <br>

                                <label class="block font-medium text-sm text-gray-700">Dosis por tambo en Ml.</label>
                            <input type="number" readonly
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.dosis_tambo_ml">
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
                                    <th>Ultima actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="solucionByProduct in solucionesByProduct.data">
                                        <td class="px-4 py-2"> {{ solucionByProduct.id }}</td>
                                        <td class="px-4 py-2"> {{ props.producto.nombre }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.enfermedad.nombre }} en {{ solucionByProduct.tipoFlor.nombre }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.dosis_bomba_ml }} </td>
                                        <td class="px-4 py-2"> {{ solucionByProduct.dosis_tambo_ml }} </td>
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
            <div class="flex-none w-14 h-14">
            </div>
        </div>
    </AppLayout>

   
           
</template>