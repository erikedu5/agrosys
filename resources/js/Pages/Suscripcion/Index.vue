<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object,
    isOnTrial: Boolean,
    hasActiveSubscription: Boolean,
    daysRemaining: Number,
    status: String,
    trialEndsAt: String,
    subscription: Object,
});

const goToBillingPortal = () => {
    router.get(route('suscripcion.portal'));
};

const cancelSubscription = () => {
    if (confirm('¿Estás seguro de que deseas cancelar tu suscripción? Seguirás teniendo acceso hasta el final del período actual.')) {
        router.post(route('suscripcion.cancel'));
    }
};

const resumeSubscription = () => {
    router.post(route('suscripcion.resume'));
};
</script>

<template>
    <AppLayout title="Estado de Suscripción">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Estado de Suscripción
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Estado de la Suscripción -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Estado de tu Suscripción
                        </h3>
                        <span
                            :class="{
                                'bg-green-100 text-green-800': hasActiveSubscription,
                                'bg-yellow-100 text-yellow-800': isOnTrial,
                                'bg-red-100 text-red-800': !hasActiveSubscription && !isOnTrial
                            }"
                            class="px-3 py-1 rounded-full text-sm font-semibold"
                        >
                            {{ status }}
                        </span>
                    </div>

                    <!-- Trial Activo -->
                    <div v-if="isOnTrial" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h4 class="text-sm font-medium text-blue-800">
                                    Período de Prueba Activo
                                </h4>
                                <p class="mt-1 text-sm text-blue-700">
                                    Te quedan <strong>{{ daysRemaining }} días</strong> de prueba gratuita. 
                                    Tu período de prueba finaliza el <strong>{{ trialEndsAt }}</strong>.
                                </p>
                                <div class="mt-3">
                                    <Link
                                        :href="route('suscripcion.create')"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 disabled:opacity-25 transition"
                                    >
                                        Activar Suscripción Ahora
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Suscripción Activa -->
                    <div v-else-if="hasActiveSubscription && subscription" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Estado</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ subscription.stripe_status }}
                                </p>
                            </div>
                            <div v-if="subscription.ends_at">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Finaliza</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ subscription.ends_at }}
                                </p>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex space-x-3">
                                <button
                                    @click="goToBillingPortal"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition"
                                >
                                    Administrar Suscripción
                                </button>

                                <button
                                    v-if="subscription.on_grace_period"
                                    @click="resumeSubscription"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring focus:ring-green-200 disabled:opacity-25 transition"
                                >
                                    Reactivar Suscripción
                                </button>

                                <button
                                    v-else
                                    @click="cancelSubscription"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 disabled:opacity-25 transition"
                                >
                                    Cancelar Suscripción
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Suscripción Vencida -->
                    <div v-else class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h4 class="text-sm font-medium text-red-800">
                                    Suscripción Vencida
                                </h4>
                                <p class="mt-1 text-sm text-red-700">
                                    Tu período de prueba ha finalizado. Activa tu suscripción para continuar usando el sistema.
                                </p>
                                <div class="mt-3">
                                    <Link
                                        :href="route('suscripcion.create')"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 disabled:opacity-25 transition"
                                    >
                                        Activar Suscripción
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de la Empresa -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Información de la Empresa
                    </h3>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ empresa.nombre }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ empresa.email || 'No especificado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">RFC</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ empresa.rfc || 'No especificado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Teléfono</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ empresa.telefono || 'No especificado' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
