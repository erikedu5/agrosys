<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Buscar' },
    // Espera (ms) tras la última tecla antes de avisar al padre; evita una petición por letra.
    debounce: { type: Number, default: 300 },
});

const emit = defineEmits(['update:modelValue']);

const texto = ref(props.modelValue);
let timer = null;

const enviar = () => {
    clearTimeout(timer);
    timer = null;
    if (texto.value !== props.modelValue) emit('update:modelValue', texto.value);
};

const onInput = (event) => {
    texto.value = event.target.value;
    clearTimeout(timer);
    // Al vaciar el campo se actualiza de inmediato.
    if (texto.value === '' || props.debounce <= 0) {
        enviar();
        return;
    }
    timer = setTimeout(enviar, props.debounce);
};

// Cambios hechos por el padre (p. ej. limpiar filtros) se reflejan en el campo.
watch(() => props.modelValue, (value) => {
    if (timer === null && value !== texto.value) texto.value = value;
});

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <label class="relative block">
        <span class="sr-only">{{ placeholder }}</span>
        <svg class="pointer-events-none absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" /></svg>
        <input :value="texto" @input="onInput" @keydown.enter.prevent="enviar"
            type="search" autocomplete="off" enterkeyhint="search" :placeholder="placeholder"
            class="block h-14 w-full rounded-xl border-2 border-gray-300 bg-white pl-12 pr-4 text-[17px] text-gray-900 placeholder:text-gray-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
    </label>
</template>
