import fs from 'node:fs';
import path from 'node:path';
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

// In DDEV the dev server is exposed on https://<project>.ddev.site:5173 (see .ddev/config.yaml).
const origin = process.env.DDEV_PRIMARY_URL ? `${process.env.DDEV_PRIMARY_URL}:5173` : 'http://localhost:5173';
const hotFile = 'var/vite.hot';

/** Tells PHP (src/Vite.php) where the dev server is, and reloads the page when templates or posts change. */
function starlite() {
    return {
        name: 'starlite',
        apply: 'serve',
        configureServer(server) {
            server.httpServer?.once('listening', () => fs.writeFileSync(hotFile, origin));
            const removeHotFile = () => fs.rmSync(hotFile, { force: true });
            process.on('exit', removeHotFile);
            ['SIGINT', 'SIGTERM', 'SIGHUP'].forEach((signal) => process.on(signal, () => process.exit()));

            // Templates, posts and translations are not JS modules, so Vite has no HMR for them: reload the page.
            // (CSS and JS in resources/ are handled by Vite itself.)
            const reload = (file) => {
                if (/\.(twig|md)$/.test(file) || /\/translations\/[^/]+\.php$/.test(file)) {
                    server.ws.send({ type: 'full-reload' });
                }
            };
            // add/unlink too, so new and deleted templates or posts also refresh the page.
            ['change', 'add', 'unlink'].forEach((event) => server.watcher.on(event, reload));
        },
    };
}

export default defineConfig({
    plugins: [tailwindcss(), starlite()],
    publicDir: false,
    // Only variables prefixed VITE_PUBLIC_ are ever inlined into the bundle; secrets stay server-side.
    envPrefix: 'VITE_PUBLIC_',
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
        // Publishes source maps next to the bundle in public/build (readable source in production devtools).
        sourcemap: true,
        rolldownOptions: {
            input: ['resources/js/app.js'],
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin,
        cors: { origin: process.env.DDEV_PRIMARY_URL ?? /^https?:\/\/localhost(:\d+)?$/ },
        // Anchored to the project root: a bare '**/var/**' would also match the root itself (/var/www/html)
        // and silently stop all file watching.
        watch: { ignored: ['vendor', 'var', '.ddev'].map((dir) => path.resolve(dir) + '/**') },
    },
});
