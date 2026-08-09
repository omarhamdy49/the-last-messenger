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
                // Its own entry on purpose: the genealogy page dynamic-imports
                // it (with its d3 dependencies) only when the tree canvas nears
                // the viewport. It must never ride along in app.js.
                'resources/js/genealogy-tree.js',
                'resources/js/admin/app.js',
                'resources/js/admin/rich-text.js',
            ],
            refresh: true,
        }),
    ],
});
