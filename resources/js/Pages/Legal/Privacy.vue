<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    version: String,
});
</script>

<template>
    <Head title="Aviso de Privacidad" />

    <div class="min-h-screen bg-white text-slate-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight">Aviso de Privacidad</h1>
                    <p class="mt-2 text-sm text-slate-500">Version: {{ version }}</p>
                </div>
                <Link :href="route('plans.index')" class="text-sm font-semibold text-emerald-700 hover:underline">
                    Ver planes
                </Link>
            </div>

            <div class="prose prose-slate max-w-none mt-8">
                <p>
                    Este documento es un borrador inicial. Debe ser revisado para cumplimiento con LFPDPPP (Mexico)
                    y ajustarse a la operacion real (responsable, finalidades, transferencias, derechos ARCO, etc.).
                </p>

                <h2>1. Datos que se recaban</h2>
                <p>
                    Datos de cuenta (nombre, correo), datos de empresa (nombre, contacto), y datos opcionales de facturacion
                    cuando el cliente los proporciona.
                </p>

                <h2>2. Finalidades</h2>
                <p>
                    Operar la plataforma, soporte, comunicaciones transaccionales (suscripcion/trial), y mejora del servicio.
                </p>

                <h2>3. Conservacion</h2>
                <p>
                    Se conservan por el tiempo necesario para prestar el servicio y obligaciones legales aplicables.
                </p>

                <h2>4. Derechos ARCO</h2>
                <p>
                    Se debe habilitar un canal de contacto para solicitudes de acceso, rectificacion, cancelacion u oposicion.
                </p>

                <h2>5. Cambios al aviso</h2>
                <p>
                    Podemos actualizar este aviso; registramos la version aceptada por la empresa.
                </p>
            </div>
        </div>
    </div>
</template>

