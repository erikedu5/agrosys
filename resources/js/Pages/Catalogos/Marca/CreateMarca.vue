
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({marca: Object});
    
    const form = useForm({
        nombre: props.marca !== undefined ? props.marca.nombre : '',
        id: props.marca !== undefined ? props.marca.id: null
    });
    
    const submit = () => {
        if (props.marca == undefined) {
            form.post(route('marca.store'), form);
        } else {
            form.put(route('marca.update', props.marca.id), form);
        }
    }
</script>

<template>
    <AppLayout title="CrearMarca">  
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Marcas
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
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.nombre">
                            <InputError class="mt-2" :message="form.errors.nombre" />
                                <br>
                                <br>

                            <button 
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg
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