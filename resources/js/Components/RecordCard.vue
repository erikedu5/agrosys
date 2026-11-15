<script setup>
defineProps({
    eyebrow: { type: [String, Number], default: '' },
    title: { type: [String, Number], default: '' },
    subtitle: { type: String, default: '' },
    // [{ label, value, wide }]
    fields: { type: Array, default: () => [] },
    selected: { type: Boolean, default: false },
});

const vacio = (value) => value === null || value === undefined || value === '';
</script>

<template>
    <article class="ui-card flex flex-col gap-3 p-4"
        :class="selected ? 'border-2 border-blue-600 bg-blue-50 dark:border-blue-400 dark:bg-blue-950' : ''">
        <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p v-if="!vacio(eyebrow)" class="truncate text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ eyebrow }}</p>
                <h2 class="break-words text-[17px] font-bold leading-snug">{{ title }}</h2>
                <p v-if="subtitle" class="break-words text-sm text-gray-600 dark:text-gray-400">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.badge" class="flex shrink-0 flex-wrap justify-end gap-1.5">
                <slot name="badge" />
            </div>
        </header>

        <slot />

        <dl v-if="fields.length" class="grid grid-cols-2 gap-x-4 gap-y-2.5 text-sm">
            <div v-for="field in fields" :key="field.label" class="min-w-0" :class="field.wide ? 'col-span-2' : ''">
                <dt class="text-xs text-gray-600 dark:text-gray-400">{{ field.label }}</dt>
                <dd class="break-words font-semibold">{{ vacio(field.value) ? '—' : field.value }}</dd>
            </div>
        </dl>

        <span class="flex-1"></span>

        <footer v-if="$slots.actions" class="flex flex-wrap gap-2 border-t border-gray-100 pt-3 dark:border-gray-700">
            <slot name="actions" />
        </footer>
    </article>
</template>
