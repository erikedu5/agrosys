import { ref } from 'vue';
import { defineStore } from 'pinia';
import { countPendingOperations, retryBlockedOperations, synchronize } from '@/Offline/sync/syncEngine';

export const useSyncStore = defineStore('offline-sync', () => {
    const syncing = ref(false);
    const pendingCount = ref(0);
    const lastResult = ref(null);
    const lastError = ref(null);

    async function refreshCount() { pendingCount.value = await countPendingOperations().catch(() => 0); }
    async function run() {
        if (syncing.value) return lastResult.value;
        syncing.value = true;
        lastError.value = null;
        try { lastResult.value = await synchronize(); return lastResult.value; }
        catch (error) { lastError.value = error; throw error; }
        finally { syncing.value = false; await refreshCount(); }
    }
    async function retryBlocked() { await retryBlockedOperations(); return run(); }
    return { syncing, pendingCount, lastResult, lastError, refreshCount, run, retryBlocked };
});
