import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
// Starlite's defaults (build output, manifest, DDEV dev server, page reloads): see the file for details.
import starlite from './vendor/starlite/framework/resources/vite/starlite.js';

export default defineConfig({
    plugins: [
        tailwindcss(),
        starlite({
            // One entry per page or feature, so heavy JS only loads where it's used.
            input: ['resources/js/app.js'],
        }),
    ],
});
