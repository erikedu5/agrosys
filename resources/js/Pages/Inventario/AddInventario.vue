
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({
        producto: Object, 
    });
    
    const form = useForm({
        nombre: props.producto !== undefined ? props.producto.nombre : '',
        cantidad: 0,
        id: props.producto !== undefined ? props.producto.id: null,
    });
    
    const submit = () => {
        form.post(route('inventario.addInventario'), form);
    }
</script>

<template>
    <AppLayout title="AgregarInventarioProducto">  
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Agregar producto al inventario
            </h2>
        </template>

        <div class="flex">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">
                        <form @submit.prevent="submit">
                            <label class="block font-medium text-sm text-gray-700">Nombre</label>
                            <input type="text" readonly
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.nombre">
                                <br>
                                <br>

                            
                            <label class="block font-medium text-sm text-gray-700">Actualmente en stock hay: {{ props.producto.cantidad }}</label>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Cantidad de productos a agregar</label>
                            <input type="number" min="1" step="0.01"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.cantidad">
                            <InputError class="mt-2" :message="form.errors.cantidad" />
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
            <div class="flex-none w-14 h-14">
            </div>
        </div>
    </AppLayout>
</template>