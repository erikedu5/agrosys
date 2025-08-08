
<script setup>
    import AppLayout from'@/Layouts/AppLayout.vue';
    import{ useForm }from'@inertiajs/vue3';
    import InputError from '@/Components/InputError.vue';

    const props=defineProps({
        usuario: Object,
        sucursales: {
            type: Array,
            default: []
        }
    });

    const form = useForm({
        name: props.usuario !== undefined ? props.usuario.name : '',
        password: '',
        email: props.usuario !== undefined ? props.usuario.email: '',
        tipo: props.usuario !== undefined ? props.usuario.tipo: '',
        id: props.usuario !== undefined ? props.usuario.id: null,
        id_sucursal: props.usuario !== undefined ? props.usuario.id_sucursal: props.sucursales[0].is_sucursal,
    });

    console.log(props.sucursales);

    const submit = () => {
        if (props.usuario == undefined) {
            form.post(route('usuario.store'), form);
        } else {
            form.put(route('usuario.update', props.usuario.id), form);
        }
    }
</script>

<template>
    <AppLayout title="CrearUsuario">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Crear Usuario
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
                                v-model="form.name">
                            <InputError class="mt-2" :message="form.errors.name" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Password</label>
                            <input type="password"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.password">
                            <InputError class="mt-2" :message="form.errors.password" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Email</label>
                            <input type="email"
                                class="form-input w-full rounded-md shadow-sm"
                                v-model="form.email">
                            <InputError class="mt-2" :message="form.errors.email" />
                            <br>
                            <br>

                            <label class="block font-medium text-sm text-gray-700">Tipo de usuario</label>
                            <select v-model="form.tipo" id="tipo" name="tipo"
                             class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="" disabled>Selecione</option>
                            <option value="admin">Administrador</option>
                            <option value="vendedor">Vendedor</option>
                            <option value="inventario">Inventario</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.tipo" />

                            <br>

                            <label class="block font-medium text-sm text-gray-700">Sucursal</label>
                            <select v-model="form.id_sucursal" id="sucursal" name="sucursal"
                             class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="" disabled>Selecione</option>
                            <option v-for="sucursal in sucursales" :value="sucursal.id" :key="sucursal.id">
                                {{ sucursal.nombre }}
                            </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.id_sucursal" />

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
