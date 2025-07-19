<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import NotificationsPanel from '@/Components/NotificationsPanel.vue';

defineProps({
    pedidos: Array,
    sucursales: Array,
    filtroSucursal: {
        type: [Number, String],
        default: null,
    },
});

const selected = ref(filtroSucursal);

const filtrar = () => {
    router.get(route('pedidos.index', { sucursal: selected.value }), {}, { preserveState: true });
};
</script>

<template>
    <AppLayout title="Pedidos">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Pedidos
            </h2>
        </template>

        <div class="flex gap-4 p-4">
            <div class="w-3/4">
                <div class="mb-4">
                    <select v-model="selected" @change="filtrar" class="form-select">
                        <option value="">Todas las sucursales</option>
                        <option v-for="s in sucursales" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                    </select>
                </div>
                <table class="table-auto w-full text-sm">
                    <thead>
                        <tr>
                            <th>Sucursal</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Nombre</th>
                            <th>Número</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in pedidos" :key="p.id">
                            <td>{{ p.sucursal.nombre }}</td>
                            <td>{{ p.producto.nombre }}</td>
                            <td>{{ p.cantidad }}</td>
                            <td>{{ p.nombre_solicitante }}</td>
                            <td>{{ p.numero_solicitante }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="w-1/4">
                <NotificationsPanel :sucursal-id="$page.props.auth.user.id_sucursal" />
            </div>
        </div>
    </AppLayout>
</template>
