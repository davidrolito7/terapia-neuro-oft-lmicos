import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ command }) => ({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: command === 'serve',
        }),
    ],
}));
