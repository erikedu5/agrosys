import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { createContext, SourceTextModule, SyntheticModule } from 'node:vm';

const deferred = () => {
    let resolve, reject;
    const promise = new Promise((ok, fail) => { resolve = ok; reject = fail; });
    return { promise, resolve, reject };
};
const confirmation = () => ({ response: { status: 409, data: {
    code: 'DEVICE_SESSION_CONFIRMATION_REQUIRED', message: 'Confirma el cierre de tus otras sesiones.',
} } });

async function harness({ get = async () => ({ data: { authenticated: true } }), download } = {}) {
    let activeContext = { userId: '1', branchId: '1' };
    const redirects = [];
    const context = createContext({ navigator: { onLine: true }, window: {
        location: { pathname: '/dashboard', replace: path => redirects.push(path) },
    } });
    const dependencies = {
        vue: { ref: value => ({ value }), computed: fn => ({ get value() { return fn(); } }) },
        pinia: { defineStore: (_, setup) => setup },
        axios: { default: { get } },
        '@/Offline/services/catalogSync': { downloadInitialCatalog: download },
        '@/Offline/services/context': { getOfflineContext: () => activeContext },
    };
    const module = new SourceTextModule(await readFile(new URL('../../resources/js/stores/connectivity.js', import.meta.url), 'utf8'), { context });
    await module.link(name => {
        const exports = dependencies[name];
        return new SyntheticModule(Object.keys(exports), function () {
            for (const [key, value] of Object.entries(exports)) this.setExport(key, value);
        }, { context });
    });
    await module.evaluate();
    return { store: module.namespace.useConnectivityStore(), redirects,
        changeBranch: id => { activeContext = { userId: '1', branchId: id }; } };
}

test('a late 409 cannot restore the error after confirming and refreshing successfully', async () => {
    const old = deferred(), started = deferred();
    let calls = 0;
    const h = await harness({ download: () => {
        if (++calls === 1) { started.resolve(); return old.promise; }
        return { syncedAt: 'fresh-after-confirmation' };
    } });
    const previous = h.store.healthCheck({ syncCatalog: true });
    await started.promise;
    assert.equal(await h.store.healthCheck({ syncCatalog: true }), true);
    old.reject(confirmation());
    assert.equal(await previous, false);
    assert.equal(h.store.mode.value, 'online');
    assert.equal(h.store.syncIssue.value, null);
    assert.equal(h.store.lastError.value, null);
    assert.equal(h.store.lastSyncAt.value, 'fresh-after-confirmation');
});

test('a request from the previous branch cannot restore a warning in the new branch', async () => {
    const old = deferred(), started = deferred();
    const h = await harness({ download: () => { started.resolve(); return old.promise; } });
    const previous = h.store.healthCheck({ syncCatalog: true });
    await started.promise;
    h.changeBranch('2');
    old.reject(confirmation());
    assert.equal(await previous, false);
    assert.equal(h.store.lastError.value, null);
    assert.equal(h.store.syncIssue.value, null);
    assert.equal(await h.store.healthCheck(), true);
    assert.equal(h.store.mode.value, 'online');
});

test('an old unauthorized response cannot redirect a newly validated session', async () => {
    const old = deferred();
    let calls = 0;
    const h = await harness({ get: () => ++calls === 1 ? old.promise : { data: { authenticated: true } } });
    const previous = h.store.healthCheck();
    assert.equal(await h.store.healthCheck(), true);
    old.reject({ response: { status: 401 } });
    assert.equal(await previous, false);
    assert.deepEqual(h.redirects, []);
    assert.equal(h.store.mode.value, 'online');
});

test('a current confirmation error is still surfaced and does not pretend activation succeeded', async () => {
    const h = await harness({ download: async () => { throw confirmation(); } });
    assert.equal(await h.store.healthCheck({ syncCatalog: true }), false);
    assert.equal(h.store.mode.value, 'sync_error');
    assert.equal(h.store.syncIssue.value.status, 409);
    assert.equal(h.store.lastError.value.response.data.code, 'DEVICE_SESSION_CONFIRMATION_REQUIRED');
});
