<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: String, required: true },
    // Variación en % contra el periodo anterior (null = sin comparación)
    delta: { type: Number, default: null },
    deltaLabel: { type: String, default: '' },
    upIsGood: { type: Boolean, default: true },
    hint: { type: String, default: '' },
    tone: { type: String, default: 'neutral' }, // neutral | critical | serious
});

const deltaTone = computed(() => {
    if (props.delta === null || Math.abs(props.delta) < 0.5) return 'flat';
    return (props.delta > 0) === props.upIsGood ? 'good' : 'bad';
});
const deltaText = computed(() => {
    if (props.delta === null) return '';
    const abs = Math.abs(props.delta);
    return `${abs >= 10 ? abs.toFixed(0) : abs.toFixed(1)}%`;
});
</script>

<template>
    <div class="flex flex-col gap-1 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 sm:p-5">
        <p class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400">
            <span v-if="tone !== 'neutral'" class="h-2 w-2 shrink-0 rounded-full" :class="tone === 'critical' ? 'bg-[#d03b3b]' : 'bg-[#ec835a]'" aria-hidden="true"></span>
            {{ label }}
        </p>
        <p class="whitespace-nowrap text-[22px] font-semibold leading-8 tracking-tight text-gray-900 dark:text-white sm:text-[28px] sm:leading-9">{{ value }}</p>
        <p v-if="delta !== null" class="flex flex-wrap items-center gap-x-1.5 text-[13px]">
            <span class="inline-flex items-center gap-0.5 font-semibold"
                :class="{ 'text-[#006300] dark:text-[#0ca30c]': deltaTone === 'good', 'text-red-700 dark:text-red-400': deltaTone === 'bad', 'text-gray-600 dark:text-gray-400': deltaTone === 'flat' }">
                <svg v-if="deltaTone !== 'flat'" class="h-3.5 w-3.5" :class="delta < 0 ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" /></svg>
                <span class="sr-only">{{ delta > 0 ? 'Subió' : delta < 0 ? 'Bajó' : 'Sin cambio' }}</span>
                {{ deltaText }}
            </span>
            <span class="text-gray-500 dark:text-gray-400">{{ deltaLabel }}</span>
        </p>
        <p v-else-if="hint" class="text-[13px] text-gray-500 dark:text-gray-400">{{ hint }}</p>
    </div>
</template>
