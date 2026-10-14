import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        watch: {
            // Flutter build output and plugin symlinks are unrelated to the web app.
            ignored: ['**/clients/**', '**/public/storage/**', '**/storage/logs/**'],
        },
        cors: {
            origin: process.env.VITE_APP_URL ?? "http://localhost:8000",
        },
    },
});
