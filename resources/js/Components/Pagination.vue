<script setup>

import { defineComponent } from "vue";
import { Link } from '@inertiajs/vue3';

defineComponent({
    components: {
        Link,
    }
});

defineProps({
        links: {
            type: Array,
            default: []
        },
        prefix: {
            type: String,
            default: ''
        }
    });
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap gap-2">
            <template v-for="(link, key) in links" :key="key">
                <div
                    v-if="link.url === null"
                    class="inline-flex h-11 min-w-11 items-center justify-center rounded-lg border border-gray-200 px-3 text-sm font-semibold text-gray-400 dark:border-gray-700 dark:text-gray-500"
                    v-html="link.label === 'pagination.previous'
                            ? 'Anterior' : link.label === 'pagination.next'
                            ? 'Siguiente' : link.label"
                />

                <Link
                    v-else
                    class="inline-flex h-11 min-w-11 items-center justify-center rounded-lg border px-3 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                    :class="link.active ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'"
                    :href="link.url + prefix"
                    v-html="link.label === 'pagination.previous'
                            ? 'Anterior' : link.label === 'pagination.next'
                            ? 'Siguiente' : link.label"
                />
            </template>
        </div>
    </div>
</template>

