export const DATABASE_NAME = 'agrosys-offline';
export const DATABASE_VERSION = 1;

export const stores = Object.freeze({
    products: { keyPath: 'id', indexes: [['serverId', 'serverId'], ['sku', 'sku'], ['barcode', 'barcode'], ['nameNormalized', 'nameNormalized'], ['updatedAtServer', 'updatedAtServer']] },
    productPrices: { keyPath: 'id', indexes: [['productId', 'productId'], ['branchId', 'branchId'], ['productBranch', ['productId', 'branchId'], { unique: true }]] },
    stockSnapshots: { keyPath: 'id', indexes: [['productId', 'productId'], ['branchId', 'branchId'], ['productBranch', ['productId', 'branchId'], { unique: true }], ['syncedAt', 'syncedAt']] },
    customers: { keyPath: 'id', indexes: [['serverId', 'serverId'], ['branchId', 'branchId'], ['nameNormalized', 'nameNormalized'], ['updatedAtServer', 'updatedAtServer']] },
    sales: { keyPath: 'id', indexes: [['serverId', 'serverId'], ['operationId', 'operationId', { unique: true }], ['branchId', 'branchId'], ['status', 'status'], ['occurredAt', 'occurredAt']] },
    saleItems: { keyPath: 'id', indexes: [['saleId', 'saleId'], ['productId', 'productId']] },
    payments: { keyPath: 'id', indexes: [['saleId', 'saleId'], ['status', 'status'], ['occurredAt', 'occurredAt']] },
    inventoryMovements: { keyPath: 'id', indexes: [['operationId', 'operationId'], ['branchId', 'branchId'], ['productId', 'productId'], ['syncStatus', 'syncStatus'], ['occurredAt', 'occurredAt']] },
    outbox: { keyPath: 'operationId', indexes: [['status', 'status'], ['nextAttemptAt', 'nextAttemptAt'], ['createdAt', 'createdAt']] },
    syncState: { keyPath: 'id', indexes: [] },
    offlineSession: { keyPath: 'id', indexes: [['userId', 'userId'], ['deviceId', 'deviceId'], ['branchId', 'branchId'], ['offlineExpiresAt', 'offlineExpiresAt']] },
    appMetadata: { keyPath: 'key', indexes: [] },
});
