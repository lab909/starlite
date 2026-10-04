import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
// Starlite's defaults (entry points, the 'datastar' and 'starlite' aliases, build output, manifest,
// DDEV dev server, page reloads): see the file for details.
import starlite from './vendor/starlite/framework/resources/vite/starlite.js';

export default defineConfig({
    plugins: [
        tailwindcss(),
        // Entry points: resources/js/app.js plus every resources/js/pages/*.js, one bundle per page or
        // feature, so heavy JS only loads where it's used. Pass `input: [...]` to list them yourself.
        starlite(),
    ],
});
