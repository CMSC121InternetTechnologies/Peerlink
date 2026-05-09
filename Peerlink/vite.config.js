import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    // Bind the dev server to IPv4 loopback. By default Vite picks `[::1]`
    // (IPv6 loopback) on Windows, which Chrome's CSP parser rejects when
    // combined with a port wildcard (`[::1]:*` is rejected as an invalid
    // source). Pinning to 127.0.0.1 avoids the bracket-notation issue
    // entirely so our CSP `http://127.0.0.1:*` rule covers Vite cleanly.
    server: {
        host: '127.0.0.1',
    },
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
                // Dashboard SPA logic. Was previously served as
                // public/app.js with a manual <script src> + filemtime
                // cache-busting; now bundled by Vite for hashing,
                // tree-shaking, and HMR in dev.
                'resources/js/dashboard.js',
            ],
            refresh: true,
        }),
    ],
});
