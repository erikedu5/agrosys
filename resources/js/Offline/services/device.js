import { openOfflineDatabase, requestAsPromise, runTransaction } from '../database/db';

export async function getOrCreateDeviceId() {
    const database = await openOfflineDatabase();
    const existing = await requestAsPromise(database.transaction('appMetadata', 'readonly').objectStore('appMetadata').get('deviceId'));
    if (existing?.value) return existing.value;
    const value = crypto.randomUUID();
    await runTransaction(['appMetadata'], 'readwrite', transaction => transaction.objectStore('appMetadata').put({ key: 'deviceId', value }));
    return value;
}

export async function nextDeviceSequence() {
    const result = { value: 0 };
    await runTransaction(['syncState'], 'readwrite', transaction => {
        const request = transaction.objectStore('syncState').get('device-sequence');
        request.onsuccess = () => {
            result.value = Number(request.result?.value ?? 0) + 1;
            transaction.objectStore('syncState').put({ id: 'device-sequence', value: result.value });
        };
    });
    return result.value;
}
