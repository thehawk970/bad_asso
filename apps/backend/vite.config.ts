import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import fs from 'node:fs';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

const httpsConfig =
    process.env.VITE_SERVER_KEY && process.env.VITE_SERVER_CERT
        ? {
              key: fs.readFileSync(process.env.VITE_SERVER_KEY),
              cert: fs.readFileSync(process.env.VITE_SERVER_CERT),
          }
        : undefined;

export default defineConfig({
    resolve: {
        dedupe: ['react', 'react-dom'],
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        https: httpsConfig,
        // URL publique écrite dans public/hot pour que Laravel sache où trouver le serveur Vite
        origin: process.env.VITE_DEV_ORIGIN ?? 'https://localhost:5173',
        cors: true,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: null,
            devOptions: { enabled: true },
            // Mettre sw.js à la racine public/ pour que la scope soit /
            outDir: 'public',
            // Indiquer au SW où se trouvent les assets buildés
            buildBase: '/build/',
            workbox: {
                globPatterns: ['**/*.{js,css,ico,png,svg,woff2}'],
                globDirectory: 'public/build',
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pages-cache',
                            expiration: { maxEntries: 50, maxAgeSeconds: 86400 },
                        },
                    },
                ],
            },
            manifest: {
                name: 'Badminton Belvésois',
                short_name: 'Bad Belvésois',
                description: 'Gestion du club Badminton Belvésois',
                theme_color: '#18181b',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/',
                scope: '/',
                icons: [
                    {
                        src: '/pwa-64x64.png',
                        sizes: '64x64',
                        type: 'image/png',
                    },
                    {
                        src: '/pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                    {
                        src: '/maskable-icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable',
                    },
                ],
            },
        }),
    ],
});
