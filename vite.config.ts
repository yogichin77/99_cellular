import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import tailwindcss from "@tailwindcss/vite";
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa'; // <- Tambahkan ini

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['favicon.ico', 'robots.txt', 'apple-touch-icon.png'],
            manifest: {
                name: 'Nama Aplikasi Kamu',
                short_name: 'NamaPendek',
                description: 'Deskripsi singkat aplikasi kamu',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                display: 'standalone',
                icons: [
                    {
                        src: '/icons/pwa-icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/icons/pwa-icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            }
        })
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
});
