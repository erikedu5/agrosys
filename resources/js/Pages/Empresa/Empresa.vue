
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';

    const props=defineProps({empresa: Object});
    
    const form = useForm({
        id: props.empresa !== null ? props.empresa.id: null,
        nombre: props.empresa !== null ? props.empresa.nombre : '',
        direccion: props.empresa !== null ? props.empresa.direccion : '',
        telefono: props.empresa !== null ? props.empresa.telefono : '',
        email: props.empresa !== null ? props.empresa.email : '',
        rfc: props.empresa !== null ? props.empresa.rfc : '',
        aviso: props.empresa !== null ? props.empresa.aviso : '',
    });
    
    const submit = () => {
        if (props.empresa == null) {
            form.post(route('empresa.store'), form);
        } else {
            form.put(route('empresa.update', props.empresa.id), form);
        }
    }
</script>

<template>
    <AppLayout title="CrearEmpresa">  
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Empresa
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
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Dirección</label>
                            <input type="text" 
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.direccion">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Telefono</label>
                            <input type="tel"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.telefono">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Email</label>
                            <input type="email"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.email">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">RFC</label>
                            <input type="text"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.rfc">
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Aviso</label>
                            <textarea 
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.aviso"
                                rows="8">
                            </textarea>
                            <br>
                            <br>

                            <button class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded 
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