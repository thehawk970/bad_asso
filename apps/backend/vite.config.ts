import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import fs from 'node:fs';
import { defineConfig } from 'vite';

const httpsConfig =
    process.env.VITE_SERVER_KEY && process.env.VITE_SERVER_CERT
        ? {
              key: fs.readFileSync(process.env.VITE_SERVER_KEY),
              cert: fs.readFileSync(process.env.VITE_SERVER_CERT),
          }
        : undefined;

export default defineConfig({
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
    ],
});
