import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    // terser squeezes a bit more out of the JS than the default esbuild minifier
    // (PageSpeed flagged ~7 KB). Slightly slower build, smaller payload.
    build: {
        minify: 'terser',
    },
    // laravel-vite-plugin disables publicDir, so during HMR the @font-face
    // urls (/fonts/*.woff2) resolve against the Vite origin and 404. Proxy
    // them back to the Laravel server so self-hosted fonts load in dev too.
    server: {
        proxy: {
            '/fonts': 'http://localhost:8000',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/js/app.js',
                // NOT an entry: app.js dynamic-imports it, so Vite code-splits
                // it into its own lazily-fetched chunk automatically. As an
                // entry it had to be referenced by a hand-built URL at runtime,
                // which is what broke it on phones and looped HMR in dev.
                'resources/js/admin/app.js',
                'resources/js/admin/rich-text.js',
            ],
            refresh: true,
        }),
    ],
});
