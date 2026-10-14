<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
    empresas: {
        type: Array,
        default: []
    },
    isSuperAdmin: {
        type: Boolean,
        default: false
    },
    showDeleted: {
        type: Boolean,
        default: false
    },
    all: {
        type: Boolean,
        default: false
    }
});

const q = ref('');
const showDeleted = ref(props.showDeleted);

watch(q, (value) => {
    router.get(route('empresa.index', { q: value, deleted: showDeleted.value }), {}, { preserveState: true });
});

const toggleDeleted = () => {
    showDeleted.value = !showDeleted.value;
    router.get(route('empresa.index', { q: q.value, deleted: showDeleted.value }), {}, { preserveState: true });
};

const desactivar = (id) => {
    if (confirm("¿Desea desactivar la empresa?")) {
        useForm({}).delete(route('empresa.destroy', id));
    }
};

const restaurar = (id) => {
    if (confirm("¿Desea restaurar la empresa?")) {
        useForm({}).put(route('empresa.restore', id));
    }
};
</script>

<template>
    <AppLayout title="Empresa">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Empresas
            </h2>
            <br>
            <input type="text" class="form-input rounded-md shadow-sm w-full" v-model="q"
                placeholder="Buscar empresa...">
        </template>

        <hr class="my-6">
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 justify-end" v-if="!all">
            <div class="flex space-x-2">
                <Link
                    v-if="isSuperAdmin"
                    :href="route('empresa.subscriptions.index')"
                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                           hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                           focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                           dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white"
                >
                Panel suscripciones
                </Link>
                <Link :href="route('empresa.create')"
                    class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded
                                           hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
                                           focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                           dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                Crear empresa
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
        <div class="flex max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 mb-3 mt-3">
            <!-- Vista en tarjetas -->
            <div class="md:hidden grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                <div v-for="empresa in empresas.data" :key="empresa.id" class="rounded-lg boder p-4 bg-white shadow-lg">
                    <div>
                        <div class="text-sm text-gray-500">Nombre de la empresa</div>
                        <div class="font-semibold text-gray-900">{{ empresa.nombre }}</div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm">

                        <div>
                            <div class="text-gray-500">Dirección</div>
                            <div>{{ empresa.direccion }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Telefono</div>
                            <div>{{ empresa.telefono }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Email</div>
                            <div>{{ empresa.telefono }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">RFC</div>
                            <div>{{ empresa.rfc }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Campos de precio visibles</div>
                            <div>{{ empresa.mostrar_campos_precio ? 'Sí' : 'No' }}</div>
                        </div>
                        <div v-if="isSuperAdmin || all">
                            <div class="text-gray-500">Facturación automática</div>
                            <div>{{ empresa.enviar_facturas_automaticas ? 'Sí' : 'No' }}</div>
                        </div>
                        <div v-if="isSuperAdmin">
                            <div class="text-gray-500">Ventas bloqueadas</div>
                            <div>{{ empresa.ventas_bloqueadas ? 'Sí' : 'No' }}</div>
                        </div>
                    </div>
                    <div>
                            <div class="text-gray-500">Aviso</div>
                            <div>{{ empresa.aviso }}</div>
                        </div>
                    <div v-if="isSuperAdmin && empresa.ventas_bloqueadas">
                        <div class="text-gray-500">Motivo bloqueo</div>
                        <div>{{ empresa.motivo_bloqueo || 'Sin motivo registrado' }}</div>
                    </div>
                    <div class="flex justify-end mt-3">
                        <div v-if="!showDeleted" class="inline-flex rounded-md shadow-sm" role="group">
                            <Link :href="route('empresa.edit', empresa.id)"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Actualizar
                            </Link>
                            <Link href="" @click.prevent="desactivar(empresa.id)" v-if="!all"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md  hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Desactivar
                            </Link>
                        </div>
                        <div v-else>
                            <Link href="" @click.prevent="restaurar(empresa.id)" v-if="!all"
                                class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                            Restaurar
                            </Link>
                        </div>
                    </div>

                    <div name="Pagination">
                        <Pagination class="mt-6" :links="empresas.links" />
                    </div>
                </div>
            </div>


            <div class="relative overflow-x-auto hidden md:block w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs uppercase bg-gray-50">
                        <tr class="[&>th]:px-4 [&>th]:py-3">
                            <th>Id</th>
                            <th>Nombre de la empresa</th>
                            <th>Dirección</th>
                            <th>Telefono</th>
                            <th>Email</th>
                            <th>RFC</th>
                            <th>Campos de precio visibles</th>
                            <th v-if="isSuperAdmin || all">Facturación automática</th>
                            <th v-if="isSuperAdmin">Ventas bloqueadas</th>
                            <th v-if="isSuperAdmin">Motivo de bloqueo</th>
                            <th>Aviso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="empresa in empresas.data" :key="empresa.id">
                            <td class="px-4 py-2"> {{ empresa.id }}</td>
                            <td class="px-4 py-2"> {{ empresa.nombre }} </td>
                            <td class="px-4 py-2"> {{ empresa.direccion }} </td>
                            <td class="px-4 py-2"> {{ empresa.telefono }} </td>
                            <td class="px-4 py-2"> {{ empresa.email }} </td>
                            <td class="px-4 py-2"> {{ empresa.rfc }} </td>
                            <td class="px-4 py-2">
                                {{ empresa.mostrar_campos_precio ? 'Sí' : 'No' }}
                            </td>
                            <td v-if="isSuperAdmin || all" class="px-4 py-2">
                                {{ empresa.enviar_facturas_automaticas ? 'Sí' : 'No' }}
                            </td>
                            <td v-if="isSuperAdmin" class="px-4 py-2">
                                {{ empresa.ventas_bloqueadas ? 'Sí' : 'No' }}
                            </td>
                            <td v-if="isSuperAdmin" class="px-4 py-2">
                                {{ empresa.motivo_bloqueo ?? 'Sin motivo registrado' }}
                            </td>
                            <td class="px-4 py-2"> {{ empresa.aviso }} </td>
                            <td class="px-4 py-2">
                                <div v-if="!showDeleted" class="inline-flex rounded-md shadow-sm" role="group">
                                    <Link :href="route('empresa.edit', empresa.id)"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Actualizar
                                    </Link>
                                    <Link href="" @click.prevent="desactivar(empresa.id)" v-if="!all"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md  hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Desactivar
                                    </Link>
                                </div>
                                <div v-else>
                                    <Link href="" @click.prevent="restaurar(empresa.id)" v-if="!all"
                                        class="px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
                                    Restaurar
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div name="Pagination">
                    <Pagination class="mt-6" :links="empresas.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
