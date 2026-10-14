<script setup>
import { ref, onMounted } from 'vue';

const messages = ref([]);

onMounted(() => {
    window.addEventListener('notify', (e) => {
        const id = Date.now();
        const { message, type = 'info' } = e.detail || {};
        messages.value.push({ id, message, type });
        setTimeout(() => {
            messages.value = messages.value.filter((m) => m.id !== id);
        }, 3000);
    });
});
</script>

<template>
    <div class="fixed top-4 right-4 z-50 space-y-2">
        <div
            v-for="m in messages"
            :key="m.id"
            :class="[
                'px-4 py-2 rounded shadow text-white',
                m.type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            ]"
        >
            {{ m.message }}
        </div>
    </div>
</template>

