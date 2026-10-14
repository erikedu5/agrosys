import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { setOfflineContext } from '@/Offline/services/context';
import { createBrowserLogout } from '@/utils/browserLogout';

export const logoutBrowser = createBrowserLogout({
    http: axios,
    logoutUrl: () => route('logout'),
    beforeLogout: () => router.cancel(),
    clearContext: () => setOfflineContext(),
    redirect: () => window.location.replace('/'),
});
