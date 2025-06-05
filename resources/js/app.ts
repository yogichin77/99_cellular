import '../css/app.css';

import { clearOfflineTransaksis, getAllOfflineTransaksis } from '@/lib/Idb_Kasir';
import { createInertiaApp } from '@inertiajs/vue3';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';

window.addEventListener('online', async () => {
    const offlineTransaksis = await getAllOfflineTransaksis();
    for (const transaksi of offlineTransaksis) {
        try {
            await axios.post('/api/transaksi', transaksi);
        } catch (e) {
            console.error('Gagal kirim transaksi offline:', e);
        }
    }
    await clearOfflineTransaksis();
});

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || '99 Cellular';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
// Register Service Worker (client-side only)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((reg) => console.log('ServiceWorker registered:', reg))
            .catch((err) => console.error('ServiceWorker registration failed:', err));
    });
}
