import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import axios from 'axios';
import { downloadInitialCatalog } from '@/Offline/services/catalogSync';

export const useConnectivityStore = defineStore('connectivity', () => {
    const mode = ref('recovering');
    const lastSyncAt = ref(null);
    const lastError = ref(null);
    const pendingCount = ref(0);
    // Motivo del estado sync_error: { type: 'conflicts' | 'session' | 'server', ... }
    const syncIssue = ref(null);
    let healthTimer = null;
    let initialized = false;
    let recoveryHandler = null;
    let posWarmed = false;

    // En sync_error el servidor sí responde: la app se usa en línea con normalidad
    // y solo la sincronización offline queda pendiente de revisión.
    const isUsableOnline = computed(() => ['online', 'sync_error'].includes(mode.value));
    const isLimited = computed(() => !isUsableOnline.value);

    async function warmOfflineShell() {
        if (posWarmed || !('caches' in window)) return;
        try {
            if ('serviceWorker' in navigator) {
                await navigator.serviceWorker.ready;
            }
            const cache = await caches.open('agrosys-shell-v10');
            for (const path of ['/dashboard', '/venta']) {
                const response = await fetch(path, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'text/html,application/xhtml+xml',
                        'X-Offline-Shell': '1',
                    },
                });
                if (response.ok && response.headers.get('content-type')?.includes('text/html')) {
                    const documentKey = new Request(path, {
                        method: 'GET',
                        credentials: 'same-origin',
                    });
                    await cache.put(documentKey, response.clone());
                }
            }
            posWarmed = true;
        } catch {
            // A previous cached POS remains usable if this refresh fails.
        }
    }

    function redirectToOfflineHome() {
        if (['/dashboard', '/venta'].includes(window.location.pathname)) return;
        window.location.replace('/dashboard');
    }

    async function healthCheck({ syncCatalog = false } = {}) {
        // Solo se pasa a "recuperando" al salir de offline; en línea o con un problema
        // de sincronización la app sigue usable mientras se revisa.
        if (mode.value === 'offline') mode.value = 'recovering';
        lastError.value = null;
        try {
            const { data: health } = await axios.get('/api/v1/offline/health', { timeout: 5000, headers: { Accept: 'application/json' } });
            if (!health.authenticated) {
                const error = new Error('OFFLINE_SESSION_REQUIRES_ONLINE_LOGIN');
                error.response = { status: 401 };
                throw error;
            }
            await warmOfflineShell();
            if (syncCatalog) {
                const bootstrap = await downloadInitialCatalog();
                lastSyncAt.value = bootstrap.syncedAt;
            }
            if (recoveryHandler) {
                const recovery = await recoveryHandler();
                pendingCount.value = recovery?.pending ?? pendingCount.value;
                lastSyncAt.value = recovery?.syncedAt ?? lastSyncAt.value;
                if (recovery?.conflicts > 0) {
                    syncIssue.value = { type: 'conflicts', count: recovery.conflicts };
                    mode.value = 'sync_error';
                    return false;
                }
            }
            syncIssue.value = null;
            mode.value = 'online';
            return true;
        } catch (error) {
            const status = error?.response?.status;
            lastError.value = error;
            if (status && ![502, 503, 504].includes(status)) {
                syncIssue.value = [401, 419].includes(status)
                    ? { type: 'session' }
                    : { type: 'server', status, message: error.response?.data?.message ?? null };
                mode.value = 'sync_error';
                return false;
            }
            syncIssue.value = null;
            mode.value = 'offline';
            redirectToOfflineHome();
            return false;
        }
    }

    function markOffline() {
        syncIssue.value = null;
        mode.value = 'offline';
        redirectToOfflineHome();
    }

    function initialize({ enabled = false, catalogEnabled = false, onRecovered = null } = {}) {
        if (initialized) return;
        if (!enabled) {
            mode.value = 'online';
            return;
        }
        initialized = true;
        recoveryHandler = onRecovered;
        window.addEventListener('offline', markOffline);
        window.addEventListener('online', () => healthCheck({ syncCatalog: catalogEnabled }));
        window.addEventListener('focus', () => mode.value !== 'online' && healthCheck({ syncCatalog: false }));
        healthCheck({ syncCatalog: catalogEnabled });
        healthTimer = window.setInterval(() => healthCheck({ syncCatalog: false }), 45000);
    }

    return { mode, lastSyncAt, lastError, pendingCount, syncIssue, isUsableOnline, isLimited, healthCheck, initialize };
});
