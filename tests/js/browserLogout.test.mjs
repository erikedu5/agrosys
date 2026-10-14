import test from 'node:test';
import assert from 'node:assert/strict';
import { createBrowserLogout } from '../../resources/js/utils/browserLogout.js';

function fixture(post = async () => ({ status: 204 })) {
    const events = [];
    const http = {
        get: async (url, options) => { events.push(['refresh', url, options]); return { status: 204 }; },
        post: async (url, body, options) => { events.push(['logout', url, options]); return post(); },
    };
    const logout = createBrowserLogout({ http, logoutUrl: () => '/logout',
        beforeLogout: () => events.push(['cancel-visits']), clearContext: () => events.push(['clear-context']),
        redirect: () => events.push(['redirect']) });
    return { logout, events, http };
}
const error = status => Object.assign(new Error('request failed'), { response: { status } });

test('double click shares one logout and clears context only after confirmation', async () => {
    let finish;
    const h = fixture(() => new Promise(resolve => { finish = resolve; }));
    const first = h.logout();
    const second = h.logout();
    assert.equal(first, second);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(h.events.filter(e => e[0] === 'logout').length, 1);
    assert.equal(h.events.some(e => e[0] === 'clear-context'), false);
    finish({ status: 204 }); await first;
    assert.deepEqual(h.events.map(e => e[0]), ['cancel-visits', 'refresh', 'logout', 'clear-context', 'redirect']);
    assert.equal(h.events[2][2].headers.Accept, 'application/json');
});
test('one 419 renews CSRF and retries the logout once', async () => {
    let calls = 0;
    const h = fixture(async () => { if (++calls === 1) throw error(419); return { status: 204 }; });
    await h.logout();
    assert.deepEqual(h.events.map(e => e[0]), ['cancel-visits', 'refresh', 'logout', 'refresh', 'logout', 'clear-context', 'redirect']);
});
test('repeated 419 does not fake a successful logout or clear context', async () => {
    const h = fixture(async () => { throw error(419); });
    await assert.rejects(h.logout());
    assert.equal(h.events.filter(e => e[0] === 'logout').length, 2);
    assert.equal(h.events.some(e => e[0] === 'redirect'), false);
    assert.equal(h.events.some(e => e[0] === 'clear-context'), false);
});
test('an already expired session may leave the authenticated page', async () => {
    const h = fixture(async () => { throw error(401); });
    await h.logout();
    assert.deepEqual(h.events.slice(-2).map(e => e[0]), ['clear-context', 'redirect']);
});
test('network failure keeps context and allows an explicit later retry', async () => {
    let calls = 0;
    const h = fixture(async () => { if (++calls === 1) throw new Error('network lost'); return { status: 204 }; });
    await assert.rejects(h.logout());
    assert.equal(h.events.some(e => e[0] === 'clear-context'), false);
    await h.logout();
    assert.equal(h.events.filter(e => e[0] === 'redirect').length, 1);
});
test('refresh failure or an unexpected redirect cannot claim logout succeeded', async () => {
    const h = fixture(async () => ({ status: 200, data: '<html>login</html>' }));
    await assert.rejects(h.logout(), /confirmación/);
    assert.equal(h.events.some(e => e[0] === 'redirect'), false);
    h.http.get = async () => { throw new Error('offline'); };
    await assert.rejects(h.logout());
    assert.equal(h.events.filter(e => e[0] === 'logout').length, 1);
});
