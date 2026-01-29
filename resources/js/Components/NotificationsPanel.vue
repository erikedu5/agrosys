<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    sucursalId: {
        type: Number,
        required: true,
    }
});

const show = ref(false);
const notificaciones = ref([]);
const nombreProducto = (producto) => {
    if (!producto?.nombre) {
        return 'Producto (BORRADO)';
    }
    return producto.deleted_at ? `${producto.nombre} (BORRADO)` : producto.nombre;
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.channel(`pedidos.sucursal.${props.sucursalId}`)
            .listen('PedidoCreado', (e) => {
                console.log('Nuevo pedido recibido:', e.pedido);
                notificaciones.value.unshift(e.pedido);
                show.value = true;
            });
    }
});
</script>

<template>
    <div class="fixed top-4 right-4 z-50">
        <button @click="show = !show" class="relative p-2 bg-white rounded-full shadow">
            <svg class="h-6 w-6 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path d="M14.8569 17.0817C16.7514 16.857 18.5783 16.4116 20.3111 15.7719C18.8743 14.177 17.9998 12.0656 17.9998 9.75V9.04919C17.9999 9.03281 18 9.01641 18 9C18 5.68629 15.3137 3 12 3C8.68629 3 6 5.68629 6 9L5.9998 9.75C5.9998 12.0656 5.12527 14.177 3.68848 15.7719C5.4214 16.4116 7.24843 16.857 9.14314 17.0818M14.8569 17.0817C13.92 17.1928 12.9666 17.25 11.9998 17.25C11.0332 17.25 10.0799 17.1929 9.14314 17.0818M14.8569 17.0817C14.9498 17.3711 15 17.6797 15 18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18C9 17.6797 9.05019 17.3712 9.14314 17.0818" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span v-if="notificaciones.length" class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1">
                {{ notificaciones.length }}
            </span>
        </button>
        <div v-if="show" class="mt-2 w-64 bg-white shadow-md rounded-md p-4">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-bold">Notificaciones</h3>
                <button @click="show = false">
                    <svg class="h-4 w-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <ul class="max-h-60 overflow-y-auto">
                <li v-for="n in notificaciones" :key="n.id" class="border-b py-1">
                    <span class="font-semibold">{{ nombreProducto(n.producto) }}</span> - Cantidad: {{ n.cantidad }}
                </li>
            </ul>
        </div>
    </div>
</template>
