import './bootstrap';
import '../css/app.css';
import 'v-calendar/style.css';

import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import VCalendar from 'v-calendar';
import 'v-calendar/style.css';
import { notify } from './utils/notify';
import { useConnectivityStore } from './stores/connectivity';
import { canVisitOffline } from './Offline/guards/routePolicies';
import { useSyncStore } from './stores/sync';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const application = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue, Ziggy)
            .use(VCalendar, {});

        const connectivity = useConnectivityStore(pinia);
        const sync = useSyncStore(pinia);
        const initialProps = props.initialPage.props;
        // Connectivity must always be monitored for authenticated application
        // pages. Feature flags control offline business capabilities, not the
        // user's visibility of a real outage.
        const connectivityEnabled = Boolean(initialProps.auth?.user && initialProps.sucursalActiva);
        const offlineEnabled = Boolean(connectivityEnabled && initialProps.offline?.enabled);
        connectivity.initialize({
            enabled: connectivityEnabled,
            catalogEnabled: Boolean(offlineEnabled && initialProps.offline?.catalogEnabled),
            onRecovered: initialProps.offline?.syncEnabled ? () => sync.run() : null,
        });
        if (connectivityEnabled) {
            router.on('before', (event) => {
                // `offline` can be dispatched after the user clicks. The
                // browser's synchronous signal prevents Inertia from starting
                // an XHR during that gap.
                const isOfflineNow = navigator.onLine === false || connectivity.isLimited;
                if (!isOfflineNow) return;

                event.preventDefault();
                const target = new URL(event.detail.visit.url, window.location.origin);
                if (!canVisitOffline(target.href)) {
                    notify('Esta función requiere conexión.', 'error');
                    return;
                }

                // Inertia navigation is an XHR request and cannot load a page
                // while offline. Use a document navigation so the Service
                // Worker can serve the cached Dashboard or POS shell.
                const current = `${window.location.pathname}${window.location.search}`;
                const destination = `${target.pathname}${target.search}`;
                if (current !== destination) {
                    window.location.assign(destination);
                }
            });
        }

        return application.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

router.on('error', () => {
    notify('Ocurrio un error al procesar la solicitud. Intenta nuevamente.', 'error');
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' }).catch(() => {
            console.log('Service worker registration failed');
        });
    });
}


(function(c,l,a,r,i,t,y){
    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
})(window, document, "clarity", "script", "t7l15w34pm");
