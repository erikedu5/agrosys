<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { niceMax } from './format';

/**
 * Columnas (apiladas si hay varias series) en SVG.
 * points: [{ label, fullLabel, values: { [serie.key]: number } }]
 * series: [{ key, label, color }]  — color es una variable CSS (var(--series-1))
 */
const props = defineProps({
    points: { type: Array, required: true },
    series: { type: Array, required: true },
    format: { type: Function, required: true },
    formatTick: { type: Function, default: null },
    height: { type: Number, default: 220 },
    ariaLabel: { type: String, required: true },
    // filas adicionales del tooltip, p. ej. tickets
    extra: { type: Function, default: null },
});

const root = ref(null);
const width = ref(600);
let observer;

onMounted(() => {
    observer = new ResizeObserver(([entry]) => {
        width.value = Math.max(240, Math.floor(entry.contentRect.width));
    });
    observer.observe(root.value);
});
onBeforeUnmount(() => observer?.disconnect());

const pad = { top: 8, right: 4, bottom: 26, left: 52 };
const innerW = computed(() => width.value - pad.left - pad.right);
const innerH = computed(() => props.height - pad.top - pad.bottom);

const totals = computed(() => props.points.map(p => props.series.reduce((s, serie) => s + Number(p.values[serie.key] ?? 0), 0)));
const max = computed(() => niceMax(Math.max(0, ...totals.value)));
const ticks = computed(() => [0, 0.25, 0.5, 0.75, 1].map(f => ({ value: max.value * f, y: pad.top + innerH.value * (1 - f) })));
const y = (v) => pad.top + innerH.value * (1 - v / max.value);

const band = computed(() => innerW.value / Math.max(1, props.points.length));
const barW = computed(() => Math.max(3, Math.min(24, band.value * 0.68)));

// Segmentos apilados con 2px de separación; el de arriba lleva la punta redondeada.
const columns = computed(() => props.points.map((p, i) => {
    const cx = pad.left + band.value * i + band.value / 2;
    let base = pad.top + innerH.value;
    const visibles = props.series.filter(s => Number(p.values[s.key] ?? 0) > 0);
    const segments = visibles.map((serie, j) => {
        const h = innerH.value * (Number(p.values[serie.key]) / max.value);
        const bottom = base - (j > 0 ? 2 : 0);
        const top = Math.min(bottom, base - h);
        const height = bottom - top;
        const isTop = j === visibles.length - 1;
        const r = isTop ? Math.min(4, height, barW.value / 2) : 0;
        const x0 = cx - barW.value / 2;
        const x1 = cx + barW.value / 2;
        const d = `M${x0},${bottom} L${x0},${top + r} Q${x0},${top} ${x0 + r},${top} L${x1 - r},${top} Q${x1},${top} ${x1},${top + r} L${x1},${bottom} Z`;
        base = base - h;
        return { key: serie.key, color: serie.color, d };
    });
    return { i, cx, x: pad.left + band.value * i, segments };
}));

const labelEvery = computed(() => Math.max(1, Math.ceil(props.points.length / Math.max(2, Math.floor(innerW.value / 64)))));

const active = ref(null);
const activePoint = computed(() => active.value === null ? null : props.points[active.value]);
const tooltipLeft = computed(() => {
    if (active.value === null) return 0;
    const cx = columns.value[active.value].cx;
    return Math.min(Math.max(cx, 90), width.value - 90);
});

const onKey = (e) => {
    if (!props.points.length) return;
    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
        e.preventDefault();
        const step = e.key === 'ArrowRight' ? 1 : -1;
        const start = active.value ?? (step > 0 ? -1 : props.points.length);
        active.value = Math.min(props.points.length - 1, Math.max(0, start + step));
    }
};
</script>

<template>
    <div ref="root" class="relative w-full">
        <svg :width="width" :height="height" role="img" :aria-label="ariaLabel" tabindex="0"
            class="block overflow-visible rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
            @keydown="onKey" @blur="active = null" @pointerleave="active = null">
            <g>
                <line v-for="t in ticks" :key="t.value" :x1="pad.left" :x2="width - pad.right" :y1="t.y" :y2="t.y"
                    :stroke="t.value === 0 ? 'var(--viz-baseline)' : 'var(--viz-grid)'" stroke-width="1" />
                <text v-for="t in ticks" :key="`l${t.value}`" :x="pad.left - 8" :y="t.y" dy="0.32em" text-anchor="end"
                    class="fill-[var(--viz-muted)] text-[11px] [font-variant-numeric:tabular-nums]">{{ (formatTick ?? format)(t.value) }}</text>
            </g>
            <rect v-if="active !== null" :x="columns[active].x" :y="pad.top" :width="band" :height="innerH" fill="var(--viz-hover)" />
            <g v-for="c in columns" :key="c.i">
                <path v-for="s in c.segments" :key="s.key" :d="s.d" :fill="s.color" />
                <text v-if="c.i % labelEvery === 0" :x="c.cx" :y="height - 8" text-anchor="middle" class="fill-[var(--viz-muted)] text-[11px]">{{ points[c.i].label }}</text>
                <rect :x="c.x" :y="pad.top" :width="band" :height="innerH + 4" fill="transparent" @pointerenter="active = c.i" @pointerdown="active = c.i" />
            </g>
        </svg>

        <div v-if="activePoint" class="pointer-events-none absolute top-0 z-10 min-w-[160px] -translate-x-1/2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-lg dark:border-gray-600 dark:bg-gray-900"
            :style="{ left: `${tooltipLeft}px` }" role="status">
            <p class="mb-1 text-xs font-medium text-gray-600 dark:text-gray-400">{{ activePoint.fullLabel ?? activePoint.label }}</p>
            <p v-for="s in [...series].reverse()" :key="s.key" class="flex items-center justify-between gap-4">
                <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300"><span class="h-0.5 w-3 rounded" :style="{ background: s.color }"></span>{{ s.label }}</span>
                <strong class="font-semibold text-gray-900 [font-variant-numeric:tabular-nums] dark:text-white">{{ format(activePoint.values[s.key] ?? 0) }}</strong>
            </p>
            <p v-if="series.length > 1" class="mt-1 flex justify-between gap-4 border-t border-gray-100 pt-1 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-300">Total</span>
                <strong class="font-semibold text-gray-900 dark:text-white">{{ format(series.reduce((t, s) => t + Number(activePoint.values[s.key] ?? 0), 0)) }}</strong>
            </p>
            <p v-if="extra" class="text-xs text-gray-500 dark:text-gray-400">{{ extra(activePoint) }}</p>
        </div>

        <details class="mt-2 text-sm">
            <summary class="cursor-pointer select-none text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Ver como tabla</summary>
            <div class="mt-2 max-h-64 overflow-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-left text-sm [font-variant-numeric:tabular-nums]">
                    <thead class="sticky top-0 bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-900 dark:text-gray-400">
                        <tr><th class="px-3 py-2">Fecha</th><th v-for="s in series" :key="s.key" class="px-3 py-2 text-right">{{ s.label }}</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, i) in points" :key="i" class="border-t border-gray-100 dark:border-gray-700">
                            <td class="px-3 py-1.5 text-gray-700 dark:text-gray-300">{{ p.fullLabel ?? p.label }}</td>
                            <td v-for="s in series" :key="s.key" class="px-3 py-1.5 text-right text-gray-900 dark:text-gray-100">{{ format(p.values[s.key] ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </div>
</template>
