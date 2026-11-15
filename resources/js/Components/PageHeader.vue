<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    // URL de regreso; muestra el botón de volver.
    back: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <Link v-if="back" :href="back" class="ui-btn ui-btn-icon" aria-label="Volver" title="Volver">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 19l-7-7 7-7" /></svg>
        </Link>
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">{{ title }}</h1>
            <p v-if="subtitle" class="truncate text-sm text-gray-600 dark:text-gray-400">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.default" class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
            <slot />
        </div>
    </div>
</template>
