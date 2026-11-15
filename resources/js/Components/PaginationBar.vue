<script setup>
import { Link } from '@inertiajs/vue3';

// Paginador compacto que se queda fijo al pie mientras se recorre el listado.
defineProps({
    // Paginador de Laravel: current_page, last_page, from, to, total, prev_page_url, next_page_url.
    paginator: { type: Object, default: () => ({}) },
    // Texto que se agrega a la URL de cada página (parámetros extra).
    prefix: { type: String, default: '' },
});
</script>

<template>
    <nav v-if="paginator?.last_page > 1" aria-label="Paginación"
        class="sticky bottom-3 z-20 mx-auto flex w-full max-w-md items-center gap-2 rounded-2xl border border-gray-200 bg-white/95 p-1.5 shadow-lg backdrop-blur dark:border-gray-700 dark:bg-gray-800/95">
        <Link v-if="paginator.prev_page_url" :href="paginator.prev_page_url + prefix" class="ui-btn px-3" aria-label="Página anterior">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 19l-7-7 7-7" /></svg>
            <span class="hidden sm:inline">Anterior</span>
        </Link>
        <span v-else class="ui-btn px-3 opacity-40" aria-hidden="true">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" /></svg>
            <span class="hidden sm:inline">Anterior</span>
        </span>

        <p class="min-w-0 flex-1 text-center leading-tight text-gray-900 dark:text-gray-100" aria-live="polite">
            <span class="block text-sm font-bold">Página {{ paginator.current_page }} de {{ paginator.last_page }}</span>
            <span class="block text-xs text-gray-600 dark:text-gray-400">{{ paginator.from }}–{{ paginator.to }} de {{ paginator.total }}</span>
        </p>

        <Link v-if="paginator.next_page_url" :href="paginator.next_page_url + prefix" class="ui-btn ui-btn-primary px-3" aria-label="Página siguiente">
            <span class="hidden sm:inline">Siguiente</span>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" /></svg>
        </Link>
        <span v-else class="ui-btn px-3 opacity-40" aria-hidden="true">
            <span class="hidden sm:inline">Siguiente</span>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" /></svg>
        </span>
    </nav>
</template>
