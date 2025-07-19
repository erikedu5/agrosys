<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    sucursalId: {
        type: Number,
        required: true,
    }
});

const notificaciones = ref([]);

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel(`pedidos.sucursal.${props.sucursalId}`)
            .listen('PedidoCreado', (e) => {
                notificaciones.value.unshift(e.pedido);
            });
    }
});
</script>

<template>
    <div class="p-4 bg-white shadow md:rounded-md">
        <h3 class="font-bold mb-2">Notificaciones</h3>
        <ul>
            <li v-for="n in notificaciones" :key="n.id" class="border-b py-1">
                <span class="font-semibold">{{ n.producto.nombre }}</span>
                - Cantidad: {{ n.cantidad }}
            </li>
        </ul>
    </div>
</template>
