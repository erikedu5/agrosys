<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    empresa: Object,
    status: String,
});

const isExpired = computed(() => {
    return props.status === 'Vencida';
});
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center px-4">
        <Head title="Suscripción Vencida" />

        <div class="max-w-md w-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8">
                <!-- Icon -->
                <div class="flex justify-center mb-6">
                    <div class="rounded-full bg-red-100 p-4">
                        <svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-gray-100 mb-4">
                    Suscripción Vencida
                </h2>

                <!-- Message -->
                <div class="text-center mb-6">
                    <p class="text-gray-600 dark:text-gray-400 mb-2">
                        {{ empresa.nombre }}
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                        Tu período de prueba ha finalizado. Para continuar usando el sistema, por favor activa tu suscripción.
                    </p>
                </div>

                <!-- Status Badge -->
                <div class="flex justify-center mb-6">
                    <span class="px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                        Estado: {{ status }}
                    </span>
                </div>

                <!-- Benefits List -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3 text-sm">
                        Al activar tu suscripción obtendrás:
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Acceso completo a todas las funciones
                        </li>
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Gestión de inventario y ventas
                        </li>
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Reportes y análisis
                        </li>
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Soporte técnico
                        </li>
                    </ul>
                </div>

                <!-- CTA Buttons -->
                <div class="space-y-3">
                    <Link
                        :href="route('suscripcion.create')"
                        class="w-full inline-flex justify-center items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-200 transition"
                    >
                        Activar Suscripción
                    </Link>

                    <Link
                        :href="route('suscripcion.index')"
                        class="w-full inline-flex justify-center items-center px-6 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-sm text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-600 active:bg-gray-100 focus:outline-none focus:border-gray-300 focus:ring focus:ring-gray-200 transition"
                    >
                        Ver Detalles
                    </Link>
                </div>

                <!-- Contact Support -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                        ¿Necesitas ayuda? Contacta a soporte
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
