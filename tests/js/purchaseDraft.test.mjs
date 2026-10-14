import test from 'node:test';
import assert from 'node:assert/strict';
import { purchaseDraft, purchaseKey } from '../../resources/js/utils/purchaseDraft.js';

function storage() {
    const values = new Map();
    return {
        getItem: key => values.get(key) ?? null,
        setItem: (key, value) => values.set(key, value),
        removeItem: key => values.delete(key),
    };
}

test('reload retains the key and purchase data; success clears the draft', () => {
    const session = storage();
    const first = purchaseDraft(session, 'user:branch');
    const key = purchaseKey();
    first.save({ idempotency_key: key, proveedor: 'Proveedor', fecha_compra: '2026-10-06', productos: [{ id: 1, cantidad: 5 }], abonos: [] });
    const reloaded = purchaseDraft(session, 'user:branch');
    assert.equal(reloaded.load().idempotency_key, key);
    assert.deepEqual(reloaded.load().productos, [{ id: 1, cantidad: 5 }]);
    assert.equal(reloaded.load().fecha_compra, '2026-10-06');
    reloaded.clear();
    assert.deepEqual(reloaded.load(), {});
    assert.notEqual(purchaseKey(), key);
});

test('drafts are isolated per user and branch', () => {
    const session = storage();
    purchaseDraft(session, 'user:branch1').save({ idempotency_key: purchaseKey() });
    assert.deepEqual(purchaseDraft(session, 'user:branch2').load(), {});
    assert.deepEqual(purchaseDraft(session, 'other:branch1').load(), {});
});

test('unavailable storage does not break form operations', () => {
    const unavailable = () => { throw new Error('Storage disabled'); };
    const draft = purchaseDraft({ getItem: unavailable, setItem: unavailable, removeItem: unavailable }, 'user:branch');
    assert.deepEqual(draft.load(), {});
    assert.doesNotThrow(() => draft.save({ idempotency_key: purchaseKey() }));
    assert.doesNotThrow(() => draft.clear());
});

test('HTTP fallback generates a valid UUID v4', () => {
    const key = purchaseKey({ getRandomValues: bytes => globalThis.crypto.getRandomValues(bytes) });
    assert.match(key, /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/);
});
