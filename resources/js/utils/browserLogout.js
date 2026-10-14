// One logout at a time, including clicks from different controls. Only a
// confirmed logout (or an already expired session) may clear local context.
export function createBrowserLogout({ http, logoutUrl, beforeLogout, clearContext, redirect }) {
    let pending = null;
    const refreshCsrf = () => http.get('/sanctum/csrf-cookie', {
        withCredentials: true,
        headers: { Accept: 'application/json', 'Cache-Control': 'no-cache' },
        timeout: 10000,
    });
    const send = () => http.post(logoutUrl(), {}, {
        withCredentials: true,
        headers: { Accept: 'application/json' },
        timeout: 15000,
    });
    return () => {
        if (pending) return pending;
        pending = Promise.resolve().then(async () => {
            beforeLogout?.();
            await refreshCsrf();
            try {
                let response;
                try { response = await send(); }
                catch (error) {
                    if (error.response?.status !== 419) throw error;
                    // A 419 is rejected before logout executes, so one retry
                    // with a fresh cookie is safe. Never remove CSRF protection.
                    await refreshCsrf();
                    response = await send();
                }
                if (response.status !== 204) throw new Error('No llegó una confirmación válida del cierre de sesión.');
            } catch (error) {
                if (error.response?.status !== 401) throw error;
            }
            clearContext();
            redirect();
        }).finally(() => { pending = null; });
        return pending;
    };
}
