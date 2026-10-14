import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { webcrypto } from 'node:crypto';
import { createContext, SourceTextModule, SyntheticModule } from 'node:vm';

const command = { saleId: 'sale-id', operationId: 'operation-id', branchId: '1', userId: '2', deviceId: 'device-id', total: 100, cashSessionId: null };
async function harness({ result, lost = false, existing = null, online = true, cashClosed = false } = {}) {
    const values = new Map(), pending = [], confirmed = [], requests = [];
    const context = createContext({ crypto: webcrypto, Date, localStorage: {
        getItem: k => values.get(k) ?? null, setItem: (k, v) => values.set(k, v),
    } });
    const dependencies = {
        axios: { default: {
            post: async path => {
                requests.push(path);
                if (path === '/caja/status') return { data: { current: cashClosed ? null : { id: 'cash-id' } } };
                if (lost) throw new Error('lost response');
                return { data: { results: [result] } };
            },
            get: async () => ({ data: existing }),
        } },
        '@/Offline/repositories/OfflineSaleRepository': { offlineSaleRepository: {
            storePending: async sale => { pending.push(sale); return { status: 'pending_sync' }; },
            cacheConfirmed: async (sale, ack) => confirmed.push({ sale, ack }),
        } },
        '@/Offline/services/device': { nextDeviceSequence: async () => 1 },
    };
    const module = new SourceTextModule(await readFile(new URL('../../resources/js/Domain/Sales/SaleApplicationService.js', import.meta.url), 'utf8'), { context });
    await module.link(async name => {
        if (name === '@/Offline/services/cashSession') {
            return new SourceTextModule(await readFile(new URL('../../resources/js/Offline/services/cashSession.js', import.meta.url), 'utf8'), { context });
        }
        const exports = dependencies[name];
        return new SyntheticModule(Object.keys(exports), function () {
            for (const [key, value] of Object.entries(exports)) this.setExport(key, value);
        }, { context });
    });
    await module.evaluate();
    return { service: new module.namespace.SaleApplicationService({ isUsableOnline: online }), values, pending, confirmed, requests };
}

test('a closed cash session conflict is surfaced instead of treated as a lost response', async () => {
    const h = await harness({ result: { status: 'conflict', errorCode: 'BUSINESS_CONFLICT', message: 'Caja cerrada' } });
    await assert.rejects(h.service.complete(command), /Caja cerrada/);
    assert.equal(h.pending.length, 0);
    assert.equal(h.confirmed.length, 0);
});
test('retry response keeps exact cash session in durable pending sale and never marks it confirmed', async () => {
    const h = await harness({ result: { status: 'retry' } });
    assert.equal((await h.service.complete(command)).status, 'pending_sync');
    assert.equal(h.pending[0].cashSessionId, 'cash-id');
    assert.equal(h.pending[0].operationId, command.operationId);
    assert.equal(h.confirmed.length, 0);
});
test('a conflict returned by operation lookup is never a successful sale confirmation', async () => {
    const h = await harness({ lost: true, existing: { status: 'conflict', result: { status: 'conflict' } } });
    assert.equal((await h.service.complete(command)).status, 'pending_sync');
    assert.equal(h.confirmed.length, 0);
});
test('lost reply uses only an actual central confirmation and preserves its original operation', async () => {
    const result = { status: 'confirmed', originalStatus: 'confirmed', serverSaleId: 10 };
    const h = await harness({ lost: true, existing: { status: 'confirmed', result } });
    assert.equal((await h.service.complete(command)).serverSaleId, 10);
    assert.equal(h.confirmed.length, 1);
    assert.equal(h.confirmed[0].sale.operationId, command.operationId);
    assert.equal(h.pending.length, 0);
});
test('offline sale uses cashier/branch cash reference and explicit null survives future sessions', async () => {
    const h = await harness({ online: false });
    h.values.set('cash-session:2:1', JSON.stringify('saved-cash'));
    await h.service.complete(command);
    assert.equal(h.pending[0].cashSessionId, 'saved-cash');
    h.values.set('cash-session:2:1', 'null');
    await assert.rejects(h.service.complete({ ...command, cashSessionId: 'old-page-cash' }), /Abre la caja/);
    assert.equal(h.pending.length, 1);
    assert.equal(h.requests.length, 0);
});
test('unconfirmed cash mutation prevents new sales', async () => {
    const h = await harness();
    h.values.set('cash-request:2:1', JSON.stringify({ action: 'close' }));
    await assert.rejects(h.service.complete(command), /Confirma la solicitud pendiente/);
    assert.equal(h.requests.length, 0);
    assert.equal(h.pending.length, 0);
});

test('online sale is blocked when server reports no open cash session', async () => {
    const h = await harness({ cashClosed: true });
    await assert.rejects(h.service.complete(command), /Abre la caja/);
    assert.equal(h.requests.length, 1);
    assert.equal(h.confirmed.length, 0);
    assert.equal(h.pending.length, 0);
});
