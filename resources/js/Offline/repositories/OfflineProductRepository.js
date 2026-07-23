import { openOfflineDatabase, requestAsPromise, runTransaction } from '../database/db';

const normalize = (value) => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

export class OfflineProductRepository {
    async replaceCatalog(products, customers, metadata, offlineSession = null) {
        await runTransaction(['products', 'productPrices', 'stockSnapshots', 'customers', 'syncState', 'offlineSession'], 'readwrite', (transaction) => {
            const productStore = transaction.objectStore('products');
            const priceStore = transaction.objectStore('productPrices');
            const stockStore = transaction.objectStore('stockSnapshots');
            const customerStore = transaction.objectStore('customers');

            productStore.clear();
            priceStore.clear();
            stockStore.clear();
            customerStore.clear();

            products.forEach((product) => {
                const id = String(product.id);
                const branchId = String(product.branchId);
                productStore.put({ ...product, id, branchId, nameNormalized: normalize(product.name) });
                priceStore.put({ id: `${id}:${branchId}`, productId: id, branchId, price: Number(product.price), updatedAtServer: product.updatedAtServer });
                stockStore.put({ id: `${id}:${branchId}`, productId: id, branchId, serverQuantity: Number(product.serverQuantity), localPendingDelta: 0, estimatedQuantity: Number(product.serverQuantity), syncedAt: metadata.syncedAt });
            });

            customers.forEach((customer) => customerStore.put({ ...customer, id: String(customer.id), branchId: String(customer.branchId), nameNormalized: normalize(customer.name) }));
            transaction.objectStore('syncState').put({ id: 'catalog', ...metadata });
            if (offlineSession) transaction.objectStore('offlineSession').put(offlineSession);
        });
    }

    async search(query = '') {
        const database = await openOfflineDatabase();
        const transaction = database.transaction(['products', 'stockSnapshots'], 'readonly');
        const products = await requestAsPromise(transaction.objectStore('products').getAll());
        const stock = await requestAsPromise(transaction.objectStore('stockSnapshots').getAll());
        const stockByProduct = new Map(stock.map((snapshot) => [snapshot.productId, snapshot]));
        const needle = normalize(query);

        return products
            .filter((product) => product.active && (!needle || product.nameNormalized.includes(needle) || normalize(product.sku).includes(needle) || normalize(product.barcode).includes(needle)))
            .map((product) => ({ ...product, stock: stockByProduct.get(product.id) ?? null }))
            .sort((a, b) => a.name.localeCompare(b.name, 'es'));
    }

    async findByBarcode(barcode) {
        return (await this.search(barcode)).find((product) => product.barcode === barcode) ?? null;
    }

    async getSyncState() {
        const database = await openOfflineDatabase();
        return requestAsPromise(database.transaction('syncState', 'readonly').objectStore('syncState').get('catalog'));
    }

    async mergeCatalog(products, customers, metadata) {
        const database = await openOfflineDatabase();
        const movements = await requestAsPromise(database.transaction('inventoryMovements', 'readonly').objectStore('inventoryMovements').getAll());
        const pendingDelta = movements.filter(item => item.syncStatus !== 'confirmed').reduce((map, movement) => map.set(`${movement.productId}:${movement.branchId}`, (map.get(`${movement.productId}:${movement.branchId}`) ?? 0) + Number(movement.quantityDelta)), new Map());
        await runTransaction(['products', 'productPrices', 'stockSnapshots', 'customers', 'syncState'], 'readwrite', transaction => {
            products.forEach(product => {
                const id = String(product.id);
                const branchId = String(product.branchId);
                const delta = pendingDelta.get(`${id}:${branchId}`) ?? 0;
                transaction.objectStore('products').put({ ...product, id, branchId, nameNormalized: normalize(product.name) });
                transaction.objectStore('productPrices').put({ id: `${id}:${branchId}`, productId: id, branchId, price: Number(product.price), updatedAtServer: product.updatedAtServer });
                transaction.objectStore('stockSnapshots').put({ id: `${id}:${branchId}`, productId: id, branchId, serverQuantity: Number(product.serverQuantity), localPendingDelta: delta, estimatedQuantity: Number(product.serverQuantity) + delta, syncedAt: metadata.syncedAt });
            });
            customers.forEach(customer => transaction.objectStore('customers').put({ ...customer, id: String(customer.id), branchId: String(customer.branchId), nameNormalized: normalize(customer.name) }));
            transaction.objectStore('syncState').put({ id: 'catalog', ...metadata });
        });
    }
}

export const offlineProductRepository = new OfflineProductRepository();
