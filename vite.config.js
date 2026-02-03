import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // Evitar errores CORS al servir assets a Laravel (localhost:8000 o IPs).
        // En dev reflejamos el Origin del request.
        cors: {
            origin: true,
        },
        // No fijar IP en el repo. Por defecto usamos localhost (funciona en cualquier red),
        // y si quieres exponer HMR a otros equipos, define VITE_HMR_HOST (ej: 192.168.1.50).
        origin: `http://${process.env.VITE_HMR_HOST || 'localhost'}:5173`,
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
