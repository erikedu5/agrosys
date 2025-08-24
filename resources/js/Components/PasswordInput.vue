<template>
    <div class="relative">
        <input 
            :type="mostrarPassword ? 'text' : 'password'"
            :class="computedClass"
            :placeholder="placeholder"
            :value="modelValue"
            @input="updateValue"
            ref="passwordInput"
            :autocomplete="autocomplete"
            :required="required"
            :disabled="disabled">
        <button 
            type="button"
            @click="togglePassword"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200"
            :title="mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            tabindex="-1">
            <!-- Icono de ojo abierto (mostrar contraseña) -->
            <svg v-if="!mostrarPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            <!-- Icono de ojo cerrado (ocultar contraseña) -->
            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
            </svg>
        </button>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Ingrese la contraseña'
    },
    inputClass: {
        type: String,
        default: 'form-input w-full rounded-md shadow-sm'
    },
    autocomplete: {
        type: String,
        default: 'current-password'
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const mostrarPassword = ref(false);
const passwordInput = ref(null);

// Asegurar que el padding derecho esté incluido para el botón
const computedClass = computed(() => {
    let classes = props.inputClass;
    if (!classes.includes('pr-')) {
        classes += ' pr-10';
    }
    return classes;
});

const updateValue = (event) => {
    emit('update:modelValue', event.target.value);
};

const togglePassword = async () => {
    mostrarPassword.value = !mostrarPassword.value;
    // Mantener el foco después del toggle
    await nextTick();
    if (passwordInput.value) {
        passwordInput.value.focus();
    }
};
</script>
