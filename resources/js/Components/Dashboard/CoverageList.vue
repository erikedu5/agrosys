<script setup>
import { formatNumber } from './format';

/**
 * Productos por agotarse: días de existencia según la venta diaria de los últimos 30 días.
 * Estado siempre con icono + texto, nunca solo color.
 */
defineProps({
    items: { type: Array, required: true },
});

const HORIZONTE = 14;

const estado = (p) => {
    if (p.existencia <= 0) return { label: 'Agotado', fill: 'var(--status-critical)', icon: 'x' };
    if (p.dias < 3) return { label: `${formatNumber(p.dias)} días`, fill: 'var(--status-critical)', icon: '!' };
    if (p.dias < 7) return { label: `${formatNumber(p.dias)} días`, fill: 'var(--status-serious)', icon: '!' };
    return { label: `${formatNumber(p.dias)} días`, fill: 'var(--status-warning)', icon: 'clock' };
};
</script>

<template>
    <ul v-if="items.length" class="flex flex-col divide-y divide-gray-100 dark:divide-gray-700">
        <li v-for="(p, index) in items" :key="index" class="flex flex-col gap-1.5 py-3 first:pt-0 last:pb-0">
            <div class="flex items-start justify-between gap-3">
                <p class="min-w-0 text-sm text-gray-800 dark:text-gray-200">
                    <span class="font-semibold">{{ p.nombre }}</span><span v-if="p.tamano" class="text-gray-500 dark:text-gray-400"> · {{ p.tamano }}</span>
                </p>
                <span class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-gray-900 dark:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true">
                        <circle cx="8" cy="8" r="7" :fill="estado(p).fill" />
                        <path v-if="estado(p).icon === 'x'" d="M5.5 5.5l5 5m0-5l-5 5" stroke="#fff" stroke-width="1.8" stroke-linecap="round" />
                        <path v-else-if="estado(p).icon === '!'" d="M8 4.5v4.2M8 11.2v.1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" />
                        <path v-else d="M8 4.8V8l2 1.4" stroke="#1f2937" stroke-width="1.6" stroke-linecap="round" fill="none" />
                    </svg>
                    {{ estado(p).label }}
                </span>
            </div>
            <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700" aria-hidden="true">
                <div class="h-full rounded-full" :style="{ width: `${Math.min(100, Math.max(2, (p.dias / HORIZONTE) * 100))}%`, background: estado(p).fill }"></div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Quedan {{ formatNumber(p.existencia) }} · se venden ~{{ formatNumber(p.ventaDiaria) }} al día
            </p>
        </li>
    </ul>
    <p v-else class="flex items-center justify-center gap-2 py-6 text-sm text-gray-600 dark:text-gray-400">
        <svg class="h-5 w-5 text-[#0ca30c]" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
        Ningún producto se agota en las próximas 2 semanas.
    </p>
</template>
