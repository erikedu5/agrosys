<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue';

const props = defineProps({
    trialDays: {
        type: Number,
        default: 15
    }
});

const form = useForm({
    nombre_empresa: '',
    nombre_administrador: '',
    email: '',
    password: '',
    password_confirmation: '',
    telefono: '',
    direccion: '',
    rfc: '',
});

const submit = () => {
    form.post(route('registro.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Registro de Empresa" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">
                Registra tu Empresa
            </h2>
            <p class="text-center text-sm text-gray-600">
                Comienza tu prueba gratuita de <strong>{{ trialDays }} días</strong>. Se requiere tarjeta (sin cargo hasta que finalice la prueba)
            </p>
        </div>

        <form @submit.prevent="submit">
            <!-- Información de la Empresa -->
            <div class="space-y-4">
                <h3 class="font-semibold text-gray-700 border-b pb-2">Información de la Empresa</h3>
                
                <div>
                    <InputLabel for="nombre_empresa" value="Nombre de la Empresa *" />
                    <TextInput
                        id="nombre_empresa"
                        v-model="form.nombre_empresa"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.nombre_empresa" />
                </div>

                <div>
                    <InputLabel for="direccion" value="Dirección *" />
                    <TextInput
                        id="direccion"
                        v-model="form.direccion"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.direccion" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="telefono" value="Teléfono" />
                        <TextInput
                            id="telefono"
                            v-model="form.telefono"
                            type="tel"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.telefono" />
                    </div>

                    <div>
                        <InputLabel for="rfc" value="RFC" />
                        <TextInput
                            id="rfc"
                            v-model="form.rfc"
                            type="text"
                            class="mt-1 block w-full"
                            maxlength="13"
                        />
                        <InputError class="mt-2" :message="form.errors.rfc" />
                    </div>
                </div>
            </div>

            <!-- Información del Administrador -->
            <div class="space-y-4 mt-6">
                <h3 class="font-semibold text-gray-700 border-b pb-2">Administrador de la Empresa</h3>
                
                <div>
                    <InputLabel for="nombre_administrador" value="Nombre Completo *" />
                    <TextInput
                        id="nombre_administrador"
                        v-model="form.nombre_administrador"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.nombre_administrador" />
                </div>

                <div>
                    <InputLabel for="email" value="Email *" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="password" value="Contraseña *" />
                    <PasswordInput
                        v-model="form.password"
                        placeholder="Mínimo 8 caracteres"
                        input-class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirmar Contraseña *" />
                    <PasswordInput
                        v-model="form.password_confirmation"
                        placeholder="Confirme su contraseña"
                        input-class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
            </div>

            <div class="flex items-center justify-between mt-6">
                <Link
                    :href="route('login')"
                    class="text-sm text-gray-600 hover:text-gray-900 underline"
                >
                    ¿Ya tienes cuenta? Inicia sesión
                </Link>

                <PrimaryButton
                    class="ml-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Registrarse
                </PrimaryButton>
            </div>

            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                <p class="text-xs text-green-700 text-center">
                    <strong>{{ trialDays }} días de prueba gratuita</strong> - Se requiere tarjeta de crédito (sin cargo inmediato)
                </p>
            </div>
        </form>
    </AuthenticationCard>
</template>
