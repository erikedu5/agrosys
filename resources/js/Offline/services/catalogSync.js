import axios from 'axios';
import { offlineProductRepository } from '../repositories/OfflineProductRepository';
import { withDeviceId } from './device';
import { runTransaction } from '../database/db';

export async function downloadInitialCatalog() {
    const { data } = await withDeviceId(deviceId => axios.get('/api/v1/offline/bootstrap', {
        headers: { Accept: 'application/json', 'X-Device-ID': deviceId },
        timeout: 20000,
    }));

    const metadata = {
        schemaVersion: data.schemaVersion,
        syncedAt: data.syncedAt,
        userId: data.user.id,
        branchId: data.branch.id,
    };
    const existing = await offlineProductRepository.getSyncState();
    if (existing) {
        await offlineProductRepository.mergeCatalog(data.products, data.customers, metadata);
        await runTransaction(['offlineSession'], 'readwrite', transaction => transaction.objectStore('offlineSession').put(data.offlineSession));
    } else {
        await offlineProductRepository.replaceCatalog(data.products, data.customers, metadata, data.offlineSession);
    }

    return data;
}
