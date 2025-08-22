<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, default: () => [] }
});

const query = ref('');
const results = computed(() => {
    if (!query.value) return [];
    return props.items.filter(i => i.label.toLowerCase().includes(query.value.toLowerCase()));
});

const go = (item) => {
    query.value = '';
    router.visit(route(item.route, item.params));
};
</script>

<template>
  <div class="relative">
    <input v-model="query" type="text" placeholder="Buscar..." class="border rounded px-2 py-1 focus:outline-none focus:border-brand" />
    <ul v-if="results.length" class="absolute bg-white border mt-1 w-full z-10 max-h-40 overflow-y-auto">
      <li v-for="item in results" :key="item.label" @click="go(item)" class="px-2 py-1 hover:bg-gray-100 cursor-pointer">
        {{ item.label }}
      </li>
    </ul>
  </div>
</template>
