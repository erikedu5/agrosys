<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { usePersistedForm } from '@/stores/formStore';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

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
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: reset,
    });
};
</script>

<template>
    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <div>
            <div class="mb-4 text-center">
            <h1 class="text-2xl md:text-4xl font-bold text-green-700">Bienvenido</h1>
            </div>
            <div class="text-gray-700">
            <p class="mb-4 text-justify">
                Agrosys es una plataforma diseñada para gestionar eficientemente el inventario
                de tiendas de agroquímicos, donde puede:
            </p>
            <ul class="list-none  pl-5 mb-4">
                    <li>📦 Registrar productos y controlar el inventario.</li>
                    <li>📊 Generar ventas.</li>
                    <li>🚜 Administrar clientes.</li>
                    <li>⚡ Ayudar a personal detras de la vitrina con dosis y enfermedades.</li>
            </ul>
            <p>
                Optimiza tu negocio con Agrosys y lleva el control de tu inventario de manera rápida y sencilla.
            </p>
            </div>
        </div>
        <br>
        <p class="text-center">
           <b> Inicia Sessión aquí </b>
        </p>
        <br>

        <form @submit.prevent="submit">
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

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
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
        </form>
    </AuthenticationCard>
</template>
