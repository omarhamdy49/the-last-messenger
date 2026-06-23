import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    // terser squeezes a bit more out of the JS than the default esbuild minifier
    // (PageSpeed flagged ~7 KB). Slightly slower build, smaller payload.
    build: {
        minify: 'terser',
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/js/app.js',
                'resources/js/admin/app.js',
                'resources/js/admin/rich-text.js',
            ],
            refresh: true,
        }),
    ],
});
