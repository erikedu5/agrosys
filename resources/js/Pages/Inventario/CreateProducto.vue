
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({
        producto: Object,
        clasificaciones: Array,
        marcas: Array,
        enfermedadesFlor: Array,
    });

    const form = useForm({
        nombre: props.producto !== undefined ? props.producto.nombre : '',
        id_clasificacion: props.producto !== undefined ? props.producto.id_clasificacion: 0,
        id_marca: props.producto != undefined ? props.producto.id_marca: 0,
        id: props.producto !== undefined ? props.producto.id: null,
        precio_unitario: props.producto !== undefined ? props.producto.precio_unitario: 0.0,
        ieps: props.producto !== undefined ? props.producto.ieps: 0,
        precio_ieps: props.producto !== undefined ? props.producto.precio_ieps: 0,
        tamano: props.producto !== undefined ? props.producto.tamano: '',
        cantidad: props.producto !== undefined ? props.producto.cantidad: 0,
        ingrediente_activo: props.producto !== undefined ? props.producto.ingrediente_activo: null,
        barcode: props.producto !== undefined ? props.producto.barcode : '',
        id_usuario: 0,
    });

    const submit = () => {
        if (props.producto == undefined) {
            form.post(route('inventario.store'), form);
        } else {
            form.put(route('inventario.update', props.producto.id), form);
        }
    }

    const calcularIps = () => {
        form.precio_ieps = (((parseFloat(form.precio_unitario) / 100) * parseFloat(form.ieps)) + parseFloat(form.precio_unitario)).toFixed(2);
    }

    const calcularPrecioCompra = () => {
        form.precio_unitario = (parseFloat(form.precio_ieps) - ((parseFloat(form.precio_ieps) / 100) * parseFloat(form.ieps))).toFixed(2);
    }
</script>

<template>
    <AppLayout title="CrearProducto">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Crear Nuevo Producto
            </h2>
        </template>

        <div class="flex max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">
            <div class="grow">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow-lg bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Nombre</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.nombre">
                            <InputError class="mt-2" :message="form.errors.nombre" />
                                <br>
                                <br>

                            <label class="block font-medium text-sm text-gray-700">Clasificacion</label>
                            <select v-model="form.id_clasificacion" id="id_clasificacion" name="id_clasificacion"
                             class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="" disabled>Selecione</option>
                                <option v-for="clasificacion in clasificaciones" :value="clasificacion.id" :key="clasificacion.id">
                                    {{ clasificacion.nombre }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.id_clasificacion" />
                                <br>

                            <label class="block font-medium text-sm text-gray-700">Marca</label>
                            <select v-model="form.id_marca" id="id_marca" name="id_marca"
                             class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="" disabled>Selecione</option>
                                <option v-for="marca in marcas" :value="marca.id" :key="marca.id">
                                    {{ marca.nombre }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.id_marca" />
                                <br>

                            <label class="block font-medium text-sm text-gray-700">Precio Compra</label>
                            <input type="number" step="0.01"
                                @change="calcularIps()"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.precio_unitario">
                            <InputError class="mt-2" :message="form.errors.precio_unitario" />
                                <br>
                                <br>

                            <label class="block font-medium text-sm text-gray-700">IEPS</label>
                            <select v-model="form.ieps" id="ieps" name="ieps"
                             @change="calcularIps()"
                             class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="" disabled>Selecione</option>
                            <option value="0" default>0%</option>
                            <option value="3">3%</option>
                            <option value="6">6%</option>
                            <option value="7">7%</option>
                            <option value="9">9%</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.ieps" />
                                <br>

                            <label class="block font-medium text-sm text-gray-700">Precio con ieps</label>
                            <input type="number" step="0.01"
                                @change="calcularPrecioCompra()"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.precio_ieps">
                            <InputError class="mt-2" :message="form.errors.precio_ieps" />
                                <br>
                                <br>

                            <label class="block font-medium text-sm text-gray-700">Tamaño (Agrega unidad al tamaño ejemplo: "kg", "g", "ml", "l", etc.)</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.tamano">
                            <InputError class="mt-2" :message="form.errors.tamano" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Ingrediente activo</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.ingrediente_activo">
                            <InputError class="mt-2" :message="form.errors.ingrediente_activo" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Código de barras</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.barcode">
                            <InputError class="mt-2" :message="form.errors.barcode" />
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


                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
