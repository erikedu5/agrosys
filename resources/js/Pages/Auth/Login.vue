<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { usePersistedForm } from '@/stores/formStore';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import agrosysLogo from '@/../image/agrosyslogo-word.png';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const { form, reset } = usePersistedForm('loginForm', {
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            remember: form.remember ? 'on' : '',
        }))
        .post(route('login'), {
            onFinish: reset,
        });
};

const features = [
    {
        key: 'ventas',
        title: 'Ventas rápidas y precisas',
        desc: 'Registra ventas con tickets, devoluciones y consulta de precios con fluidez.',
        icon: '💳',
        image: '/images/landing/ventas.png',
        transition: 'fade-up',
    },
    {
        key: 'ayuda',
        title: 'Ayuda de mostrador',
        desc: 'Apoya al personal con dosis, enfermedades y soluciones al instante.',
        icon: '🧪',
        image: '/images/landing/ayuda.png',
        transition: 'fade-left',
    },
    {
        key: 'usuarios',
        title: 'Administración de usuarios',
        desc: 'Control de roles y permisos para equipos y sucursales.',
        icon: '👥',
        image: '/images/landing/usuarios.png',
        transition: 'fade-right',
    },
    {
        key: 'reportes',
        title: 'Reportes claros',
        desc: 'Ventas, inventario y ganancias diarias con versiones para impresión.',
        icon: '📊',
        image: '/images/landing/reportes.png',
        transition: 'fade-down',
    },
    {
        key: 'facturacion',
        title: 'Facturación integrada',
        desc: 'Genera y administra facturas vinculadas a tus ventas.',
        icon: '🧾',
        image: '/images/landing/facturacion.svg',
        transition: 'fade-zoom',
    },
];

const current = ref(0);
let timer = null;

const next = () => {
    current.value = (current.value + 1) % features.length;
};
const prev = () => {
    current.value = (current.value - 1 + features.length) % features.length;
};

onMounted(() => {
    timer = setInterval(next, 5000);
});
onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <Head title="Iniciar sesión" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="relative min-h-screen flex">
            <!-- Left: Feature carousel (desktop only) -->
            <div class="hidden md:block flex-1 relative overflow-hidden md:mr-[420px] lg:mr-[460px]">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-teal-100 dark:from-gray-800 dark:to-gray-900" />

                <div class="relative h-full w-full flex flex-col items-center justify-center p-8">
                    <div class="w-full max-w-2xl">
                        <transition :name="features[current].transition" mode="out-in">
                            <div :key="features[current].key" class="grid md:grid-cols-2 gap-8 items-center">
                                <div>
                                    <img
                                        :src="features[current].image"
                                        :alt="features[current].title"
                                        class="w-full h-auto rounded-xl shadow-lg ring-1 ring-black/10 dark:ring-white/10 bg-white/70"
                                        loading="eager"
                                    />
                                </div>
                                <div>
                                    <div class="text-6xl mb-4">{{ features[current].icon }}</div>
                                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                                        {{ features[current].title }}
                                    </h2>
                                    <p class="text-lg md:text-xl text-gray-700 dark:text-gray-300">
                                        {{ features[current].desc }}
                                    </p>
                                </div>
                            </div>
                        </transition>

                        <!-- Controls -->
                        <div class="mt-10 flex items-center justify-between">
                            <button @click="prev" class="px-3 py-2 rounded-md bg-white/70 dark:bg-gray-800/70 hover:bg-white dark:hover:bg-gray-800 shadow text-gray-700 dark:text-gray-200">
                                ←
                            </button>
                            <div class="flex gap-2">
                                <button
                                    v-for="(f, idx) in features"
                                    :key="f.key"
                                    @click="current = idx"
                                    class="h-2.5 w-2.5 rounded-full"
                                    :class="idx === current ? 'bg-emerald-600' : 'bg-emerald-300/70 dark:bg-gray-600'"
                                    aria-label="Cambiar slide"
                                />
                            </div>
                            <button @click="next" class="px-3 py-2 rounded-md bg-white/70 dark:bg-gray-800/70 hover:bg-white dark:hover:bg-gray-800 shadow text-gray-700 dark:text-gray-200">
                                →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Fixed login panel -->
            <aside class="w-full md:w-[420px] lg:w-[460px] md:fixed md:right-0 md:top-0 md:h-screen bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 flex flex-col">
                <div class="px-8 pt-8 pb-4 flex items-center gap-3">
                    <img :src="agrosysLogo" alt="AgroSys" class="h-8 w-auto" />
                </div>

                <div class="px-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Bienvenido</h1>
                    <p class="mt-1 text-gray-600 dark:text-gray-400">Inicia sesión para continuar</p>
                </div>

                <div class="p-8">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Password" />
                            <PasswordInput
                                v-model="form.password"
                                placeholder="Ingrese su contraseña"
                                input-class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                                autocomplete="current-password"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="block mt-4">
                            <label class="flex items-center">
                                <Checkbox v-model:checked="form.remember" name="remember" />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <Link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                                Forgot your password?
                            </Link>

                            <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Log in
                            </PrimaryButton>
                        </div>

                        <!-- Mobile carousel inside the form -->
                        <div class="mt-6 md:hidden">
                            <transition :name="features[current].transition" mode="out-in">
                                <div :key="'mobile-' + features[current].key" class="space-y-4">
                                    <img
                                        :src="features[current].image"
                                        :alt="features[current].title"
                                        class="w-full h-auto rounded-lg shadow ring-1 ring-black/10 dark:ring-white/10 bg-white"
                                        loading="lazy"
                                    />
                                    <div>
                                        <div class="text-3xl mb-2">{{ features[current].icon }}</div>
                                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ features[current].title }}</h3>
                                        <p class="text-gray-700 dark:text-gray-300">{{ features[current].desc }}</p>
                                    </div>
                                </div>
                            </transition>

                            <div class="mt-4 flex items-center justify-between">
                                <button type="button" @click="prev" class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">
                                    ←
                                </button>
                                <div class="flex gap-2">
                                    <button
                                        v-for="(f, idx) in features"
                                        :key="'mobile-dot-' + f.key"
                                        type="button"
                                        @click="current = idx"
                                        class="h-2.5 w-2.5 rounded-full"
                                        :class="idx === current ? 'bg-emerald-600' : 'bg-emerald-300/70 dark:bg-gray-600'"
                                        aria-label="Cambiar slide"
                                    />
                                </div>
                                <button type="button" @click="next" class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">
                                    →
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="mt-auto px-8 pb-6 text-xs text-gray-500 dark:text-gray-500">
                    <p>
                        • Ventas • Ayuda de mostrador • Administración de usuarios • Reportes • Facturación
                    </p>
                </div>
            </aside>
        </div>
    </div>
</template>

<style scoped>
/* Base fade */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 400ms ease, transform 400ms ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Directional variants */
.fade-up-enter-active,
.fade-up-leave-active,
.fade-down-enter-active,
.fade-down-leave-active,
.fade-left-enter-active,
.fade-left-leave-active,
.fade-right-enter-active,
.fade-right-leave-active,
.fade-zoom-enter-active,
.fade-zoom-leave-active {
  transition: opacity 450ms ease, transform 450ms ease;
}

.fade-up-enter-from,
.fade-up-leave-to { opacity: 0; transform: translateY(16px); }

.fade-down-enter-from,
.fade-down-leave-to { opacity: 0; transform: translateY(-16px); }

.fade-left-enter-from,
.fade-left-leave-to { opacity: 0; transform: translateX(16px); }

.fade-right-enter-from,
.fade-right-leave-to { opacity: 0; transform: translateX(-16px); }

.fade-zoom-enter-from,
.fade-zoom-leave-to { opacity: 0; transform: scale(0.98); }
</style>
