import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ command }) => ({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            // refresh solo activo en dev; en build evita importar chokidar/fsevents
            refresh: command === 'serve',
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
}));
