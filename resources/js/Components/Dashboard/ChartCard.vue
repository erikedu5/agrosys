<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <section class="flex min-w-0 flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 sm:p-5">
        <header class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
                <p v-if="subtitle" class="text-sm text-gray-600 dark:text-gray-400">{{ subtitle }}</p>
            </div>
            <slot name="legend" />
        </header>
        <slot />
    </section>
</template>
