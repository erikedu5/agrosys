<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    version: String,
});
</script>

<template>
    <Head title="Terminos y Condiciones" />

    <div class="min-h-screen bg-white text-slate-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight">Terminos y Condiciones</h1>
                    <p class="mt-2 text-sm text-slate-500">Version: {{ version }}</p>
                </div>
                <Link :href="route('plans.index')" class="text-sm font-semibold text-emerald-700 hover:underline">
                    Ver planes
                </Link>
            </div>

            <div class="prose prose-slate max-w-none mt-8">
                <p>
                    Este documento es un borrador inicial para la operacion de suscripciones de AgroSys.
                    Debe ser revisado por un abogado antes de publicarse como version final.
                </p>

                <h2>1. Servicio</h2>
                <p>
                    AgroSys es una plataforma para administracion de inventario, ventas y operacion por sucursal.
                    El acceso se otorga por empresa bajo un esquema de suscripcion.
                </p>

                <h2>2. Prueba gratuita</h2>
                <p>
                    Los planes incluyen un periodo de prueba gratuita (trial) de 30 dias. Durante el trial el metodo de pago es opcional.
                    El sistema mostrara recordatorios antes de finalizar el trial. Al finalizar el trial, el acceso puede ser restringido
                    hasta que se active una suscripcion.
                </p>

                <h2>3. Suscripcion y renovacion</h2>
                <p>
                    La suscripcion se cobra de forma recurrente segun el plan y periodo seleccionado (mensual/anual).
                    Puedes cancelar en cualquier momento desde el portal de facturacion del proveedor de pagos.
                </p>

                <h2>4. Limites del plan</h2>
                <p>
                    Cada plan incluye limites de sucursales y dispositivos (definidos como usuarios activos por sucursal, segun configuracion).
                    Si se exceden los limites, el sistema puede bloquear la creacion de nuevas sucursales/usuarios hasta que se reduzca el uso
                    o se actualice el plan.
                </p>

                <h2>5. Soporte</h2>
                <p>
                    El soporte y horarios dependen del plan contratado. En el MVP esto se maneja como informacion y feature flag.
                </p>

                <h2>6. Cancelaciones y reembolsos</h2>
                <p>
                    Politicas de cancelacion y reembolso deben definirse explicitamente. (Pendiente de definicion legal/comercial.)
                </p>

                <h2>7. Cambios</h2>
                <p>
                    Podemos actualizar estos terminos; registramos la version aceptada por la empresa.
                </p>
            </div>
        </div>
    </div>
</template>

