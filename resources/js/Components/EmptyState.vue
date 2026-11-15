<script setup>
defineProps({
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <div class="px-4 py-12 text-center text-gray-600 dark:text-gray-400">
        <p class="text-[17px] font-bold text-gray-900 dark:text-gray-100">{{ title }}</p>
        <p v-if="text" class="mt-1.5 text-[15px]">{{ text }}</p>
        <div v-if="$slots.default" class="mt-4 flex justify-center">
            <slot />
        </div>
    </div>
</template>
