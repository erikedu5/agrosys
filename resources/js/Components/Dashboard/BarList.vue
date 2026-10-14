<script setup>
import { computed } from 'vue';

/**
 * Ranking en barras horizontales de una sola serie, con el valor en la punta.
 * items: [{ label, sublabel?, value, valueLabel, detail? }]
 */
const props = defineProps({
    items: { type: Array, required: true },
    empty: { type: String, default: 'Sin datos en este periodo.' },
});

const max = computed(() => Math.max(0, ...props.items.map(i => Number(i.value) || 0)) || 1);
</script>

<template>
    <ol v-if="items.length" class="flex flex-col gap-3.5">
        <li v-for="(item, index) in items" :key="index" class="group" :title="item.detail || undefined">
            <div class="flex items-baseline justify-between gap-3 text-sm">
                <span class="min-w-0 truncate text-gray-800 dark:text-gray-200">
                    {{ item.label }}<span v-if="item.sublabel" class="text-gray-500 dark:text-gray-400"> · {{ item.sublabel }}</span>
                </span>
                <span class="shrink-0 font-semibold text-gray-900 [font-variant-numeric:tabular-nums] dark:text-white">{{ item.valueLabel }}</span>
            </div>
            <div class="mt-1.5 h-2.5 w-full" aria-hidden="true">
                <div class="h-full rounded-r bg-[var(--series-1)] transition-[filter] group-hover:brightness-110"
                    :style="{ width: `${Math.max(1.5, (Number(item.value) / max) * 100)}%` }"></div>
            </div>
            <p v-if="item.detail" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ item.detail }}</p>
        </li>
    </ol>
    <p v-else class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ empty }}</p>
</template>
