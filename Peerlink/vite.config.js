import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // All CSS and JS files referenced by @vite() in any Blade template must
            // appear here. Vite refuses to serve files not declared in its input list.
            input: [
                'resources/css/app.css',
                'resources/css/style.css',
                'resources/css/dashboard.css',
                'resources/css/register.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
