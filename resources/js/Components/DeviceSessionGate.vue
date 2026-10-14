<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import DialogModal from './DialogModal.vue';
import { withDeviceId } from '@/Offline/services/device';
import { logoutBrowser } from '@/services/browserLogout';
import { useConnectivityStore } from '@/stores/connectivity';

const page = usePage();
const show = ref(false), busy = ref(false), error = ref(null);
const connectivity = useConnectivityStore();
let generation = 0, disposed = false;
const reconnect = () => activate();
onMounted(() => window.addEventListener('online', reconnect));
onUnmounted(() => { disposed = true; generation++; window.removeEventListener('online', reconnect); });

async function activate(confirmed = false) {
    if (disposed || busy.value || !navigator.onLine || !page.props.auth?.user || !page.props.sucursalActiva) return;
    const current = generation;
    busy.value = true;
    error.value = null;
    try {
        await withDeviceId(deviceId => axios.post('/device-session/activate', {
            device_id: deviceId,
            close_other_sessions: confirmed,
        }, { headers: { Accept: 'application/json' }, timeout: 15000 }), { userId: String(page.props.auth.user.id), branchId: String(page.props.sucursalActiva.id) });
        if (current !== generation) return;
        show.value = false;
        if (page.props.offline?.enabled) await connectivity.healthCheck({ syncCatalog: !!page.props.offline?.catalogEnabled });
    } catch (e) {
        if (current !== generation) return;
        if (e.response?.status === 402) return;
        if (e.response?.status === 401) { window.location.replace('/login'); return; }
        if (e.response?.data?.code === 'DEVICE_SESSION_CONFIRMATION_REQUIRED') {
            show.value = true;
        } else if (e.response || confirmed) {
            show.value = true;
            error.value = e.response?.data?.message ?? 'No se pudo confirmar el cambio. Reintenta cuando tengas conexión.';
        }
    } finally { busy.value = false; if (current !== generation && !disposed) activate(); }
}

async function cancel() {
    busy.value = true;
    try { await logoutBrowser(); }
    catch { error.value = 'No se pudo cerrar la sesión. Reintenta.'; }
    finally { busy.value = false; }
}

watch(() => [page.props.auth?.user?.id, page.props.sucursalActiva?.id], () => {
    generation++;
    activate();
}, { immediate: true });
</script>

<template>
    <DialogModal :show="show" :closeable="false" max-width="lg">
        <template #title>Cerrar las demás sesiones</template>
        <template #content>
            <p>Se alcanzó el número de dispositivos de esta sucursal. Para entrar aquí, confirma el cierre de todas las demás sesiones web y POS de tu usuario. Las de otros usuarios seguirán abiertas.</p>
            <p class="mt-3">Los equipos sin conexión lo detectarán al reconectarse y conservarán sus ventas pendientes. Antes de continuar, sincroniza sus ventas si es posible.</p>
            <p v-if="error" class="mt-3 text-red-600" role="alert">{{ error }}</p>
        </template>
        <template #footer>
            <button class="mr-3 px-4 py-2" :disabled="busy" @click="cancel">Cancelar y salir</button>
            <button class="rounded bg-green-700 px-4 py-2 text-white disabled:opacity-50" :disabled="busy" @click="activate(true)">Cerrar las demás sesiones y entrar</button>
        </template>
    </DialogModal>
</template>
