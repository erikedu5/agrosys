import { DATABASE_NAME, DATABASE_VERSION, stores } from './schema';

let databasePromise;

export function openOfflineDatabase() {
    if (!('indexedDB' in window)) return Promise.reject(new Error('INDEXED_DB_UNAVAILABLE'));
    if (databasePromise) return databasePromise;

    databasePromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DATABASE_NAME, DATABASE_VERSION);
        request.onerror = () => reject(request.error);
        request.onblocked = () => reject(new Error('INDEXED_DB_BLOCKED'));
        request.onupgradeneeded = () => {
            const database = request.result;
            Object.entries(stores).forEach(([storeName, definition]) => {
                const store = database.objectStoreNames.contains(storeName)
                    ? request.transaction.objectStore(storeName)
                    : database.createObjectStore(storeName, { keyPath: definition.keyPath });

                definition.indexes.forEach(([name, keyPath, options = {}]) => {
                    if (!store.indexNames.contains(name)) store.createIndex(name, keyPath, options);
                });
            });
        };
        request.onsuccess = () => resolve(request.result);
    });

    return databasePromise;
}

export async function runTransaction(storeNames, mode, callback) {
    const database = await openOfflineDatabase();
    return new Promise((resolve, reject) => {
        const transaction = database.transaction(storeNames, mode);
        const result = callback(transaction);
        transaction.oncomplete = () => resolve(result);
        transaction.onerror = () => reject(transaction.error);
        transaction.onabort = () => reject(transaction.error ?? new Error('INDEXED_DB_TRANSACTION_ABORTED'));
    });
}

export const requestAsPromise = (request) => new Promise((resolve, reject) => {
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
});
