<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    empresa: Object,
    intent: Object,
    stripeKey: String,
    plans: Array,
});

const form = useForm({
    payment_method: '',
    plan: props.plans.find(p => p.highlight)?.stripe_price_id || props.plans[0]?.stripe_price_id,
});

const cardElement = ref(null);
const stripe = ref(null);
const elements = ref(null);
const cardError = ref('');
const processing = ref(false);

const selectPlan = (planId) => {
    form.plan = planId;
};

onMounted(async () => {
    // Cargar Stripe.js
    stripe.value = window.Stripe(props.stripeKey);
    
    // Crear elementos de Stripe
    elements.value = stripe.value.elements();
    
    // Crear elemento de tarjeta
    const style = {
        base: {
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };
    
    cardElement.value = elements.value.create('card', { style });
    cardElement.value.mount('#card-element');
    
    // Listener para errores
    cardElement.value.on('change', (event) => {
        cardError.value = event.error ? event.error.message : '';
    });
});

const submit = async () => {
    if (!form.plan) {
        cardError.value = 'Por favor selecciona un plan.';
        return;
    }

    processing.value = true;
    cardError.value = '';
    
    try {
        // Crear payment method
        const { setupIntent, error } = await stripe.value.confirmCardSetup(
            props.intent.client_secret,
            {
                payment_method: {
                    card: cardElement.value,
                    billing_details: {
                        name: props.empresa.nombre,
                        email: props.empresa.email
                    }
                }
            }
        );
        
        if (error) {
            cardError.value = error.message;
            processing.value = false;
            return;
        }
        
        // Enviar payment method al servidor
        form.payment_method = setupIntent.payment_method;
        form.post(route('suscripcion.subscribe'), {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            }
        });
    } catch (err) {
        cardError.value = 'Ocurrió un error al procesar el pago. Por favor, inténtalo de nuevo.';
        processing.value = false;
    }
};
</script>

<template>
    <AppLayout title="Activar Suscripción">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Activar Suscripción
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="mb-8 text-center">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Elige el plan ideal para tu negocio
                        </h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                             Todos los planes incluyen un período de prueba gratuito de 15 días.
                        </p>
                    </div>

                    <!-- Plans Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                        <div 
                            v-for="plan in plans" 
                            :key="plan.stripe_price_id"
                            class="relative border rounded-xl p-6 cursor-pointer transition-all duration-200"
                            :class="[
                                form.plan === plan.stripe_price_id 
                                    ? 'border-indigo-500 ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' 
                                    : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700'
                            ]"
                            @click="selectPlan(plan.stripe_price_id)"
                        >
                            <div v-if="plan.highlight" class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                <span class="bg-indigo-500 text-white text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wide">
                                    Más popular
                                </span>
                            </div>

                            <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ plan.name }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ plan.description }}</p>
                            
                            <div class="mt-4 flex items-baseline">
                                <span class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">${{ plan.price }}</span>
                                <span class="ml-1 text-gray-500 dark:text-gray-400">/mes</span>
                            </div>

                            <ul class="mt-6 space-y-3">
                                <li v-for="(feature, index) in plan.features" :key="index" class="flex items-start text-sm text-gray-600 dark:text-gray-300">
                                    <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                            
                             <div class="mt-6">
                                <div 
                                    class="w-full py-2 px-4 rounded-md text-center text-sm font-semibold transition-colors"
                                    :class="[
                                        form.plan === plan.stripe_price_id
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 group-hover:bg-gray-200'
                                    ]"
                                >
                                    {{ form.plan === plan.stripe_price_id ? 'Seleccionado' : 'Seleccionar' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="max-w-2xl mx-auto">
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                                Información de Pago
                            </h3>
                            
                            <form @submit.prevent="submit">
                                <!-- Stripe Card Element -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Información de la Tarjeta
                                    </label>
                                    <div 
                                        id="card-element" 
                                        class="p-3 border border-gray-300 rounded-md bg-white"
                                    ></div>
                                    <p v-if="cardError" class="mt-2 text-sm text-red-600">{{ cardError }}</p>
                                    <InputError class="mt-2" :message="form.errors.payment_method" />
                                </div>

                                <!-- Secure Payment Notice -->
                                <div class="flex items-start mb-6">
                                    <svg class="h-5 w-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="ml-3">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            🔒 Tus pagos están seguros y encriptados con Stripe
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <a
                                        :href="route('suscripcion.index')"
                                        class="text-sm text-gray-600 hover:text-gray-900 underline"
                                    >
                                        Volver
                                    </a>

                                    <button
                                        type="submit"
                                        :disabled="processing || form.processing"
                                        :class="{ 'opacity-25': processing || form.processing }"
                                        class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-200 disabled:opacity-25 transition"
                                    >
                                        <span v-if="processing || form.processing">Procesando...</span>
                                        <span v-else>Activar Suscripción</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
#card-element {
    min-height: 40px;
}
</style>
