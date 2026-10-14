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
        <div class="flex flex-wrap mt-8">
            <template v-for="(link, key) in links" :key="key">
                <div
                    v-if="link.url === null"
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded"
                    v-html="link.label === 'pagination.previous'
                            ? 'Anterior' : link.label === 'pagination.next'
                            ? 'Siguiente' : link.label"
                />

                <Link
                    v-else
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border border-gray-300 dark:border-gray-600 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500"
                    :class="link.active ? 'bg-blue-700 text-white' : 'bg-white text-gray-800 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700'"
                    :href="link.url + prefix"
                    v-html="link.label === 'pagination.previous'
                            ? 'Anterior' : link.label === 'pagination.next'
                            ? 'Siguiente' : link.label"
                />
            </template>
        </div>
    </div>
</template>

