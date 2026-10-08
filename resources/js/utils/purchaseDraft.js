const fields = ['idempotency_key', 'proveedor', 'fecha_compra', 'total_compra', 'total_credito', 'status', 'productos', 'abonos'];

export function purchaseKey(cryptoProvider = globalThis.crypto) {
    if (cryptoProvider.randomUUID) return cryptoProvider.randomUUID();
    // getRandomValues also works on HTTP installations without randomUUID.
    const bytes = cryptoProvider.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

export function purchaseDraft(storage, scope) {
    const key = `purchase-draft:${scope}`;
    return {
        load() {
            try {
                const data = JSON.parse(storage.getItem(key));
                if (!data || !data.idempotency_key) return {};
                return Object.fromEntries(fields.filter(field => field in data).map(field => [field, data[field]]));
            } catch {
                return {};
            }
        },
        save(data) {
            try {
                storage.setItem(key, JSON.stringify(Object.fromEntries(fields.map(field => [field, data[field]]))));
            } catch {
                // Keep the in-memory form usable if browser storage is unavailable.
            }
        },
        clear() {
            try { storage.removeItem(key); } catch { /* Storage may be unavailable. */ }
        },
    };
}
