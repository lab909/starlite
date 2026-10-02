import fs from 'node:fs';
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

            server.watcher.add(['templates/**/*.twig', 'content/**/*.md']);
            server.watcher.on('change', (file) => {
                if (/\.(twig|md)$/.test(file)) {
                    server.ws.send({ type: 'full-reload' });
                }
            });
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
        watch: { ignored: ['**/vendor/**', '**/var/**'] },
    },
});
