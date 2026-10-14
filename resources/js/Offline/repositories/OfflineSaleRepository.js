import { openOfflineDatabase, requestAsPromise, runTransaction } from '../database/db';

export class OfflineSaleRepository {
    async getSession() {
        const database = await openOfflineDatabase();
        return requestAsPromise(database.transaction('offlineSession', 'readonly').objectStore('offlineSession').get('current'));
    }

    async storePending(command) {
        const session = await this.getSession();
        if (!session || new Date(session.offlineExpiresAt).getTime() <= Date.now()) throw new Error('OFFLINE_SESSION_EXPIRED');
        if (String(session.branchId) !== String(command.branchId) || String(session.userId) !== String(command.userId)) throw new Error('OFFLINE_SESSION_MISMATCH');
        if (!session.permissions?.includes('sale.create')) throw new Error('OFFLINE_SALE_NOT_AUTHORIZED');

        const result = { sequence: 0, localFolio: '' };
        await runTransaction(['sales', 'saleItems', 'payments', 'inventoryMovements', 'stockSnapshots', 'outbox', 'syncState'], 'readwrite', transaction => {
            const sequenceRequest = transaction.objectStore('syncState').get('device-sequence');
            sequenceRequest.onsuccess = () => {
                const sequence = Number(command.sequence ?? (Number(sequenceRequest.result?.value ?? 0) + 1));
                const localFolio = `${command.branchId}-${new Date(command.occurredAt).toISOString().slice(0, 10).replaceAll('-', '')}-${String(sequence).padStart(6, '0')}`;
                result.sequence = sequence;
                result.localFolio = localFolio;
                transaction.objectStore('syncState').put({ id: 'device-sequence', value: Math.max(sequence, Number(sequenceRequest.result?.value ?? 0)) });
                transaction.objectStore('sales').put({ id: command.saleId, serverId: null, operationId: command.operationId, branchId: String(command.branchId), deviceId: command.deviceId, userId: String(command.userId), customerId: String(command.customerId), localFolio, serverFolio: null, status: 'pending_sync', total: command.total, saleType: command.saleType, occurredAt: command.occurredAt, createdAt: command.occurredAt, updatedAt: command.occurredAt });
                command.items.forEach(item => {
                    transaction.objectStore('saleItems').put({ id: crypto.randomUUID(), saleId: command.saleId, productId: String(item.productId), quantity: Number(item.quantity), unitPrice: Number(item.unitPrice), total: Number(item.total) });
                    transaction.objectStore('inventoryMovements').put({ id: crypto.randomUUID(), operationId: command.operationId, branchId: String(command.branchId), productId: String(item.productId), referenceId: command.saleId, quantityDelta: -Number(item.quantity), reason: 'sale', syncStatus: 'pending', occurredAt: command.occurredAt });
                    const stockRequest = transaction.objectStore('stockSnapshots').get(`${item.productId}:${command.branchId}`);
                    stockRequest.onsuccess = () => {
                        const stock = stockRequest.result;
                        if (stock) transaction.objectStore('stockSnapshots').put({ ...stock, localPendingDelta: Number(stock.localPendingDelta ?? 0) - Number(item.quantity), estimatedQuantity: Number(stock.serverQuantity) + Number(stock.localPendingDelta ?? 0) - Number(item.quantity) });
                    };
                });
                command.payments.forEach(payment => transaction.objectStore('payments').put({ id: payment.id ?? crypto.randomUUID(), saleId: command.saleId, method: payment.method, amount: Number(payment.amount), status: 'pending_sync', occurredAt: command.occurredAt }));
                transaction.objectStore('outbox').put({ operationId: command.operationId, aggregateType: 'sale', aggregateId: command.saleId, eventType: 'SALE_COMPLETED', payload: { ...command, localFolio }, sequence, status: 'pending', attempts: 0, nextAttemptAt: null, lastErrorCode: null, lastErrorMessage: null, createdAt: command.occurredAt, updatedAt: command.occurredAt });
            };
        });

        return { saleId: command.saleId, operationId: command.operationId, status: 'pending_sync', localFolio: result.localFolio, serverFolio: null };
    }

    async markConfirmed(operationId, serverResult) {
        await runTransaction(['sales', 'outbox', 'inventoryMovements'], 'readwrite', transaction => {
            const outboxRequest = transaction.objectStore('outbox').get(operationId);
            outboxRequest.onsuccess = () => {
                const operation = outboxRequest.result;
                if (!operation) return;
                transaction.objectStore('outbox').put({ ...operation, status: 'confirmed', updatedAt: new Date().toISOString(), lastErrorCode: null, lastErrorMessage: null });
                const saleRequest = transaction.objectStore('sales').get(operation.aggregateId);
                saleRequest.onsuccess = () => saleRequest.result && transaction.objectStore('sales').put({ ...saleRequest.result, status: 'confirmed', serverId: serverResult.serverSaleId, serverFolio: serverResult.serverFolio, updatedAt: new Date().toISOString() });
                transaction.objectStore('inventoryMovements').index('operationId').openCursor(IDBKeyRange.only(operationId)).onsuccess = event => {
                    const cursor = event.target.result;
                    if (cursor) { cursor.update({ ...cursor.value, syncStatus: 'confirmed' }); cursor.continue(); }
                };
            };
        });
    }

    async cacheConfirmed(command, serverResult) {
        await runTransaction(['sales', 'saleItems', 'payments'], 'readwrite', transaction => {
            transaction.objectStore('sales').put({ id: command.saleId, serverId: serverResult.serverSaleId, operationId: command.operationId, branchId: String(command.branchId), deviceId: command.deviceId, userId: String(command.userId), customerId: String(command.customerId), localFolio: serverResult.localFolio ?? command.saleId, serverFolio: serverResult.serverFolio, status: 'confirmed', total: command.total, saleType: command.saleType, occurredAt: command.occurredAt, createdAt: command.occurredAt, updatedAt: new Date().toISOString() });
            command.items.forEach(item => transaction.objectStore('saleItems').put({ id: crypto.randomUUID(), saleId: command.saleId, productId: String(item.productId), quantity: Number(item.quantity), unitPrice: Number(item.unitPrice), total: Number(item.total) }));
            command.payments.forEach(payment => transaction.objectStore('payments').put({ id: payment.id ?? crypto.randomUUID(), saleId: command.saleId, method: payment.method, amount: Number(payment.amount), status: 'confirmed', occurredAt: command.occurredAt }));
        });
    }

    async listLocalSales() {
        const database = await openOfflineDatabase();
        const transaction = database.transaction(['sales', 'saleItems', 'outbox'], 'readonly');
        const [sales, items, outbox] = await Promise.all([
            requestAsPromise(transaction.objectStore('sales').getAll()),
            requestAsPromise(transaction.objectStore('saleItems').getAll()),
            requestAsPromise(transaction.objectStore('outbox').getAll()),
        ]);
        const operations = new Map(outbox.map(item => [item.operationId, item]));
        return sales.map(sale => ({ ...sale, items: items.filter(item => item.saleId === sale.id), operation: operations.get(sale.operationId) })).sort((a, b) => b.occurredAt.localeCompare(a.occurredAt));
    }

    async cancelUnsynced(saleId) {
        const saleList = await this.listLocalSales();
        const sale = saleList.find(item => item.id === saleId);
        // Una venta en conflicto fue rechazada por el servidor: nunca se registró allá y puede descartarse.
        if (!sale || !['pending_sync', 'conflict'].includes(sale.status) || sale.operation?.status === 'processing') throw new Error('SALE_CANNOT_BE_CANCELLED_LOCALLY');
        const occurredAt = new Date().toISOString();
        await runTransaction(['sales', 'outbox', 'inventoryMovements', 'stockSnapshots'], 'readwrite', transaction => {
            transaction.objectStore('sales').put({ ...sale, items: undefined, operation: undefined, status: 'cancelled_local', updatedAt: occurredAt });
            transaction.objectStore('outbox').put({ ...sale.operation, status: 'confirmed', lastErrorCode: 'CANCELLED_LOCAL', lastErrorMessage: 'Cancelada localmente antes de sincronizar.', updatedAt: occurredAt });
            sale.items.forEach(item => {
                transaction.objectStore('inventoryMovements').put({ id: crypto.randomUUID(), operationId: crypto.randomUUID(), branchId: sale.branchId, productId: item.productId, referenceId: sale.id, quantityDelta: Number(item.quantity), reason: 'sale_cancelled_local', syncStatus: 'confirmed', occurredAt });
                const stockRequest = transaction.objectStore('stockSnapshots').get(`${item.productId}:${sale.branchId}`);
                stockRequest.onsuccess = () => stockRequest.result && transaction.objectStore('stockSnapshots').put({ ...stockRequest.result, localPendingDelta: Number(stockRequest.result.localPendingDelta) + Number(item.quantity), estimatedQuantity: Number(stockRequest.result.estimatedQuantity) + Number(item.quantity) });
            });
        });
    }
}

export const offlineSaleRepository = new OfflineSaleRepository();
