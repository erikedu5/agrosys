<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'
const props = defineProps({
    usuarios: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    showDeleted: {
        type: Boolean,
        default: false
    },
    isSuperAdmin: {
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
            <br>
            <input type="text" class="form-input rounded-md shadow-sm w-5/6" v-model="q"
                placeholder="Buscar usuario...">

        </template>

        <hr class="my-6">
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 justify-end mb-3">
            <div class="flex space-x-2">
                <Link :href="route('usuario.create')"
                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                           hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                           focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                           dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                Crear usuario +
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
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="usuario in usuarios.data" :key="usuario.id" class="rounded-lg boder p-4 bg-white shadow-lg">

                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-sm text-gray-500">Nombre del usuario</div>
                    <div class="font-semibold text-gray-900">{{ usuario.name }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Email</div>
                            <div>{{ usuario.email }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Tipo de usuario</div>
                            <div class="capitalize">{{ usuario.tipo }}</div>
                        </div>
                        <div v-if="isSuperAdmin">
                            <div class="text-gray-500">Empresa</div>
                            <div>{{ usuario.empresa ? usuario.empresa.nombre : 'Sin empresa asignada' }}</div>
                        </div>
                        <div v-else>
                            <div class="text-gray-500">Sucursal</div>
                            <div>{{ usuario.sucursal ? usuario.sucursal.nombre : 'Sin sucursal asignada' }}</div>
                        </div>
                    </div>
                    <div class="flex justify-end mt-3">
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
                    </div>

                    <div name="Pagination">
                        <Pagination class="mt-6" :links="usuarios.links" />
                    </div>
                </div>
            </div>
            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id</th>
                            <th>Nombre del usuario</th>
                            <th>Email</th>
                            <th>Tipo de usuario</th>
                            <th>{{ isSuperAdmin ? 'Empresa' : 'Sucursal' }}</th>
                            <th>Ultima fecha de actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr>:is(td)]:px-4 [&>tr>:is(td)]:py-2">
                        <tr v-for="usuario in usuarios.data" :key="usuario.id" class="border-b">
                            <td class="whitespace-nowrap"> {{ usuario.id }}</td>
                            <td class="font-medium text-gray-900"> {{ usuario.name }} </td>
                            <td class="whitespace-nowrap"> {{ usuario.email }} </td>
                            <td class="whitespace-nowrap capitalize"> {{ usuario.tipo }} </td>
                            <td class="whitespace-nowrap">
                                <template v-if="isSuperAdmin">
                                    {{ usuario.empresa ? usuario.empresa.nombre : 'Sin empresa asignada' }}
                                </template>
                                <template v-else>
                                    {{ usuario.sucursal ? usuario.sucursal.nombre : 'Sin sucursal asignada' }}
                                </template>
                            </td>
                            <td class="whitespace-nowrap"> {{ usuario.updated_at }} </td>
                            <td class="whitespace-nowrap">
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

                <div name="Pagination">
                    <Pagination class="mt-6" :links="usuarios.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
