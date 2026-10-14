import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { webcrypto } from 'node:crypto';
import { createContext, SourceTextModule, SyntheticModule } from 'node:vm';

function database() {
    const records = new Map();
    let queue = Promise.resolve(), writes = 0;
    const runTransaction = (_, mode, callback) => {
        const operation = queue.then(() => new Promise((resolve, reject) => {
            let pending = 0, result;
            const store = {
                get(key) {
                    const request = {};
                    pending++;
                    queueMicrotask(() => {
                        try {
                            request.result = records.get(key);
                            request.onsuccess?.();
                            if (--pending === 0) resolve(result);
                        } catch (error) { reject(error); }
                    });
                    return request;
                },
                put(record) { records.set(record.key, record); writes++; },
            };
            try {
                result = callback({ objectStore: () => store });
                if (pending === 0) resolve(result);
            } catch (error) { reject(error); }
        }));
        queue = operation.catch(() => {});
        return operation;
    };
    return { records, runTransaction, writes: () => writes };
}

async function tab(db) {
    const context = createContext({ crypto: webcrypto });
    const module = new SourceTextModule(await readFile(new URL('../../resources/js/Offline/services/device.js', import.meta.url), 'utf8'), { context });
    await module.link(name => {
        const exports = name.endsWith('/db') ? { runTransaction: db.runTransaction }
            : { getOfflineContext: () => ({ userId: '13', branchId: '2' }) };
        return new SyntheticModule(Object.keys(exports), function () {
            for (const [key, value] of Object.entries(exports)) this.setExport(key, value);
        }, { context });
    });
    await module.evaluate();
    return module.namespace;
}

test('simultaneous bootstrap and activation across tabs register one identity', async () => {
    const db = database();
    const [first, second] = await Promise.all([tab(db), tab(db)]);
    const ids = await Promise.all([first.getOrCreateDeviceId(), second.getOrCreateDeviceId(), first.getOrCreateDeviceId()]);
    assert.equal(new Set(ids).size, 1);
    assert.equal(db.writes(), 1);
    assert.equal(db.records.get('deviceId:13:2').value, ids[0]);
});

test('parallel context mismatch retries reuse the first rotated identity', async () => {
    const db = database();
    db.records.set('deviceId', { key: 'deviceId', value: 'legacy-other-branch' });
    const [first, second] = await Promise.all([tab(db), tab(db)]);
    const attempts = [];
    const request = async id => {
        attempts.push(id);
        if (id === 'legacy-other-branch') throw { response: { status: 409, data: { code: 'DEVICE_CONTEXT_MISMATCH' } } };
        return id;
    };
    const ids = await Promise.all([first.withDeviceId(request), second.withDeviceId(request)]);
    assert.equal(ids[0], ids[1]);
    assert.equal(db.writes(), 2); // Initial migration and one rotation.
    assert.equal(attempts.filter(id => id === 'legacy-other-branch').length, 2);
    assert.equal(db.records.get('deviceId').value, 'legacy-other-branch');
});

test('confirmation failures never rotate or discard a saved device identity', async () => {
    const db = database();
    db.records.set('deviceId:13:2', { key: 'deviceId:13:2', value: 'existing-device' });
    const client = await tab(db);
    await assert.rejects(client.withDeviceId(async () => {
        throw { response: { status: 409, data: { code: 'DEVICE_SESSION_CONFIRMATION_REQUIRED' } } };
    }));
    assert.equal(db.records.get('deviceId:13:2').value, 'existing-device');
    assert.equal(db.writes(), 0);
});
