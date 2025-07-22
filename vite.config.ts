import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';
import path from 'path';

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
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@types': path.resolve(__dirname, './resources/types')
        }
    },
    server: {
        host: true,
        port: 5174,
        strictPort: true,
        hmr: {
            port: 5174,
            host: 'localhost'
        },
        // Allow mixed content for development with ngrok
        https: false,
        cors: true
    }
});
