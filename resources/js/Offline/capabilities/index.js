export const offlineCapabilities = Object.freeze({
    'catalog.read': true,
    'product.search': true,
    'stock.read_estimated': true,
    'sale.create': true,
    'sale.cancel_unsynced': true,
    'sale.print_local_ticket': true,
    'sync.view': true,
    'sync.retry': true,
});

export const hasOfflineCapability = (capability) => offlineCapabilities[capability] === true;
