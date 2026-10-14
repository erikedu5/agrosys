import axios from 'axios';
import { openOfflineDatabase, requestAsPromise, runTransaction } from '../database/db';
import { withDeviceId } from '../services/device';
import { getOfflineContext } from '../services/context';
import { offlineSaleRepository } from '../repositories/OfflineSaleRepository';
import { offlineProductRepository } from '../repositories/OfflineProductRepository';

const now = () => new Date().toISOString();

async function eligibleOperations() {
    const database = await openOfflineDatabase();
    const all = await requestAsPromise(database.transaction('outbox', 'readonly').objectStore('outbox').getAll());
    const current = Date.now();
    return all.filter(item => ['pending', 'failed', 'processing'].includes(item.status) && (!item.nextAttemptAt || new Date(item.nextAttemptAt).getTime() <= current)).sort((a, b) => a.sequence - b.sequence).slice(0, 50);
}

async function updateOutbox(operationId, changes) {
    const { saleStatus, ...outboxChanges } = changes;
    await runTransaction(['outbox', 'sales'], 'readwrite', transaction => {
        const request = transaction.objectStore('outbox').get(operationId);
        request.onsuccess = () => {
            if (!request.result) return;
            const updated = { ...request.result, ...outboxChanges, updatedAt: now() };
            transaction.objectStore('outbox').put(updated);
            if (saleStatus) {
                const saleRequest = transaction.objectStore('sales').get(updated.aggregateId);
                saleRequest.onsuccess = () => saleRequest.result && transaction.objectStore('sales').put({ ...saleRequest.result, status: saleStatus, updatedAt: now() });
            }
        };
    });
}

export async function countPendingOperations() {
    const database = await openOfflineDatabase();
    const all = await requestAsPromise(database.transaction('outbox', 'readonly').objectStore('outbox').getAll());
    return all.filter(item => item.status !== 'confirmed').length;
}

async function countBlockedOperations() {
    const database = await openOfflineDatabase();
    const all = await requestAsPromise(database.transaction('outbox', 'readonly').objectStore('outbox').getAll());
    return all.filter(item => item.status === 'blocked').length;
}

export async function retryBlockedOperations() {
    const database = await openOfflineDatabase();
    const all = await requestAsPromise(database.transaction('outbox', 'readonly').objectStore('outbox').getAll());
    await Promise.all(all.filter(item => item.status === 'blocked').map(item => updateOutbox(item.operationId, { status: 'pending', nextAttemptAt: null })));
}

// Solo se envían las operaciones del usuario y la sucursal de la sesión offline
// actual; las de otro contexto esperan a que ese usuario/sucursal vuelva a entrar.
const belongsTo = (operation, session) => String(operation.payload?.userId) === String(session.userId)
    && String(operation.payload?.branchId) === String(session.branchId);

export async function synchronize() {
    const session = await offlineSaleRepository.getSession();
    if (!session) return { confirmed: 0, conflicts: 0, pending: 0 };
    // Si en este navegador entró otro usuario, sus ventas pendientes no se envían con
    // esta sesión: el servidor las rechazaría y quedarían bloqueadas.
    const context = getOfflineContext();
    const sameUser = !context || context.userId === String(session.userId);
    const operations = sameUser ? (await eligibleOperations()).filter(operation => belongsTo(operation, session)) : [];
    await Promise.all(operations.map(operation => updateOutbox(operation.operationId, { status: 'processing' })));
    let confirmed = 0;
    let conflicts = 0;

    // Cada operación viaja con el dispositivo que la registró.
    const byDevice = new Map();
    operations.forEach(operation => {
        const deviceId = operation.payload?.deviceId ?? session.deviceId;
        byDevice.set(deviceId, [...(byDevice.get(deviceId) ?? []), operation]);
    });

    for (const [pushDeviceId, group] of byDevice) {
        let data;
        try {
            ({ data } = await axios.post('/api/v1/offline/sync/push', { device_id: pushDeviceId, branch_id: session.branchId, operations: group.map(operation => ({ operation_id: operation.operationId, aggregate_type: operation.aggregateType, aggregate_id: operation.aggregateId, event_type: operation.eventType, sequence: operation.sequence, occurred_at: operation.payload.occurredAt ?? operation.createdAt, payload: operation.payload })) }, { timeout: 30000, headers: { Accept: 'application/json' } }));
        } catch (error) {
            const terminal = [400, 401, 403, 422].includes(error?.response?.status);
            await Promise.all(group.map(operation => {
                const attempts = Number(operation.attempts ?? 0) + 1;
                const delay = Math.min(300000, 1000 * (2 ** attempts)) + Math.floor(Math.random() * 1000);
                return updateOutbox(operation.operationId, { status: terminal ? 'blocked' : 'failed', saleStatus: terminal ? 'conflict' : undefined, attempts, nextAttemptAt: terminal ? null : new Date(Date.now() + delay).toISOString(), lastErrorCode: terminal ? `HTTP_${error.response.status}` : 'NETWORK_ERROR', lastErrorMessage: terminal ? (error.response?.data?.message ?? 'La operación requiere revisión.') : 'No se recibió confirmación del servidor.' });
            }));
            throw error;
        }
        for (const result of data.results) {
            if (['confirmed', 'duplicate'].includes(result.status)) {
                await offlineSaleRepository.markConfirmed(result.operationId, result);
                confirmed++;
            } else if (['conflict', 'rejected', 'blocked'].includes(result.status)) {
                await updateOutbox(result.operationId, { status: 'blocked', saleStatus: 'conflict', lastErrorCode: result.errorCode, lastErrorMessage: result.message });
                conflicts++;
            } else {
                const operation = group.find(item => item.operationId === result.operationId);
                const attempts = Number(operation?.attempts ?? 0) + 1;
                const delay = Math.min(300000, 1000 * (2 ** attempts)) + Math.floor(Math.random() * 1000);
                await updateOutbox(result.operationId, { status: 'failed', attempts, nextAttemptAt: new Date(Date.now() + delay).toISOString(), lastErrorCode: result.errorCode, lastErrorMessage: result.message });
            }
        }
    }

    const database = await openOfflineDatabase();
    const syncState = await requestAsPromise(database.transaction('syncState', 'readonly').objectStore('syncState').get('catalog'));
    const { data: pulled } = await withDeviceId(deviceId => axios.get('/api/v1/offline/sync/pull', { params: { cursor: syncState?.cursor }, headers: { Accept: 'application/json', 'X-Device-ID': deviceId }, timeout: 30000 }));
    await offlineProductRepository.mergeCatalog(pulled.products, pulled.customers, { schemaVersion: pulled.schemaVersion, syncedAt: pulled.syncedAt, userId: pulled.user.id, branchId: pulled.branch.id, cursor: pulled.cursor });
    if (pulled.offlineSession) await runTransaction(['offlineSession'], 'readwrite', transaction => transaction.objectStore('offlineSession').put(pulled.offlineSession));
    return { confirmed, conflicts: await countBlockedOperations(), pending: await countPendingOperations(), syncedAt: pulled.syncedAt };
}
