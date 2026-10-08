<script setup>
import { computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: Number,
        default: 500,
    },
    message: {
        type: String,
        default: '',
    },
    url: {
        type: String,
        default: '',
    },
});

const titles = {
    404: 'No encontramos esta pagina',
    409: 'La compra ya fue registrada',
    419: 'Sesion expirada',
    429: 'Demasiadas solicitudes',
    500: 'Ocurrio un problema',
    503: 'Servicio en mantenimiento',
};

const messages = {
    404: 'Verifica el enlace o regresa al inicio.',
    409: 'Los datos enviados difieren de la compra registrada. Revisa el listado antes de registrar otra compra.',
    419: 'Por seguridad tu sesion caduco. Inicia sesion de nuevo.',
    429: 'Detectamos muchas peticiones en poco tiempo. Intenta otra vez en unos segundos.',
    500: 'Ocurrio un error inesperado. No se expone informacion sensible. Intenta mas tarde o contacta al administrador.',
    503: 'Estamos trabajando en mejoras. Vuelve a intentarlo en unos momentos.',
};

const code = computed(() => titles[props.status] ? props.status : 500);
const title = computed(() => titles[props.status] ?? titles[500]);
const displayMessage = computed(() => props.message || messages[props.status] || messages[500]);
const currentUrl = computed(() => props.url || (typeof window !== 'undefined' ? window.location.href : '/'));

onMounted(() => {
    if (typeof window !== 'undefined' && props.url && window.location.href !== props.url) {
        window.history.replaceState({}, '', props.url);
    }
});

const goHome = () => router.visit('/dashboard');

const reloadPage = () => {
    if (typeof window !== 'undefined') {
        window.location.href = currentUrl.value;
    }
};

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(currentUrl.value, { replace: true });
    }
};
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen flex items-center justify-center bg-slate-950 text-slate-100 px-4 py-10">
        <div class="w-full max-w-xl bg-slate-900/80 border border-slate-800 rounded-2xl shadow-2xl p-8 space-y-6">
            <div class="flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.08em] text-slate-400">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_0_6px_rgba(16,185,129,0.12)]" />
                <span>Error {{ code }}</span>
            </div>
            <div class="space-y-2">
                <h1 class="text-3xl font-extrabold text-slate-50 tracking-tight">
                    {{ title }}
                </h1>
                <p class="text-slate-300 leading-relaxed">
                    {{ displayMessage }}
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a v-if="status === 409" :href="route('compra.index')"
                    class="px-4 py-3 rounded-xl font-semibold bg-emerald-400 text-slate-950">
                    Revisar compras
                </a>
                <a
                    href="/dashboard"
                    class="inline-flex items-center gap-2 px-4 py-3 rounded-xl font-semibold bg-emerald-400 text-slate-950 shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:-translate-y-0.5 transition"
                    @click.prevent="goHome"
                >
                    Ir al inicio
                </a>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-3 rounded-xl font-semibold border border-slate-800 text-slate-300 hover:border-slate-600 hover:-translate-y-0.5 transition"
                    @click="goBack"
                >
                    Volver
                </button>
            </div>
            <p class="text-xs text-slate-500">
                Si el problema persiste, contacta al administrador del sistema. No se mostraron detalles técnicos.
            </p>
        </div>
    </div>
</template>
