<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { ref, watch } from 'vue';
    import { router, Link, useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue'
    const props = defineProps({
        usuarios: {
            type: Array,
            default: []
        },
        showDeleted: {
            type: Boolean,
            default: false
        }
    });

    const q = ref('');
    const showDeleted = ref(props.showDeleted);

    watch(q, (value) => {
        router.get(route('usuario.index', { q: value, deleted: showDeleted.value }), {}, { preserveState: true });
    });

    const toggleDeleted = () => {
        showDeleted.value = !showDeleted.value;
        router.get(route('usuario.index', { q: q.value, deleted: showDeleted.value }), {}, { preserveState: true });
    };

    const desactivar = (id) => {
        if (confirm("¿Desea desactivar el usuario?")) {
            useForm({}).delete(route('usuario.destroy', id));
        }
    };

    const restaurar = (id) => {
        if (confirm("¿Desea restaurar el usuario?")) {
            useForm({}).put(route('usuario.restore', id));
        }
    };
</script>

<template>
    <AppLayout title="Usuarios">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Usuarios
            </h2>
        </template>

        <hr class="my-6">

        <div class="flex">
            <div class="flex-none w-14 h-14">
            </div>
            <div class="grow h-14">
                <div class="md-col-span-2 mt-5 md:mt-0">
                    <div class="shadow bg-white md:rounded-md p-4">

                        <div class="flex justify-between">
                            <input type="text" class="form-input rounded-md shadow-sm w-5/6" v-model="q" placeholder="Buscar usuario...">
                            <div class="flex space-x-2">
                                <Link :href="route('usuario.create')"
                                      class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                           hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                           focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                           dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Crear usuario
                                </Link>
                                <button @click="toggleDeleted"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                               hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                               focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                               dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    {{ showDeleted ? 'Ver activos' : 'Ver eliminados' }}
                                </button>
                            </div>
                        </div>

                        <hr class="my-6">

                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                    <th>Id</th>
                                    <th>Nombre del usuario</th>
                                    <th>Email</th>
                                    <th>Tipo de usuario</th>
                                    <th>Ultima fecha de actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="usuario in usuarios.data" :key="usuario.id">
                                        <td class="px-4 py-2"> {{ usuario.id }}</td>
                                        <td class="px-4 py-2"> {{ usuario.name }} </td>
                                        <td class="px-4 py-2"> {{ usuario.email }} </td>
                                        <td class="px-4 py-2"> {{ usuario.tipo }} </td>
                                        <td class="px-4 py-2"> {{ usuario.updated_at }} </td>
                                        <td class="px-4 py-2">
                                            <div v-if="!showDeleted" class="inline-flex rounded-md shadow-sm" role="group">
                                                <Link :href="route('usuario.edit', usuario.id)"
                                                      class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Actualizar
                                                </Link>
                                                <Link href="" @click.prevent="desactivar(usuario.id)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md  hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Desactivar
                                                </Link>
                                            </div>
                        
                                            <div v-else>
                                                <Link href="" @click.prevent="restaurar(usuario.id)"
                                                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                                    Restaurar
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div name="Pagination">
                            <Pagination class="mt-6" :links="usuarios.links" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-none w-14 h-14">
            </div>
        </div>
    </AppLayout>
</template>
