import { readFileSync } from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue2 from '@vitejs/plugin-vue2';
import commonjs from '@rollup/plugin-commonjs';

/**
 * Production Vite build (see webpack-to-vite-roadmap.md section 5j) — a
 * single `vite build` compiles all 22 entries at once via laravel-vite-plugin,
 * emitting a manifest (public/build/manifest.json) that
 * App\View\Components\Script::coreSource() reads via Vite::asset(). This
 * replaces the earlier domain-by-domain POC scaffold, which built one entry
 * at a time (`VITE_ENTRY=name npx vite build`) to the exact legacy Mix
 * path so Blade never had to change ("Option A") — that constraint no
 * longer applies now that Script.php itself has been updated.
 *
 * `vite-entries.json` (repo root) is the single source of truth for the
 * entry list, shared with Script.php's PHP-side (folder,file) -> source-path
 * lookup so the two can never drift. Read via fs (not a JSON import
 * assertion) to avoid Node-version-dependent import-assertion syntax.
 */
const entries = JSON.parse(readFileSync(new URL('./vite-entries.json', import.meta.url)));

export default defineConfig({
    plugins: [
        laravel({
            input: Object.values(entries),
            refresh: false,
        }),
        vue2(),
    ],
    resolve: {
        extensions: ['.js', '.json', '.vue'],
        alias: [
            // These views mount with `el: '#main-body'`/`'#app'` and no
            // template/render option — Vue compiles the server-rendered
            // innerHTML as the template at runtime, which needs the full
            // build (compiler included). Vite/npm's default "vue"
            // resolution is the runtime-only build; without this alias the
            // mount silently no-ops (no error, just empty content) instead
            // of throwing.
            { find: /^vue$/, replacement: 'vue/dist/vue.esm.js' },
        ],
    },
    build: {
        rollupOptions: {
            // Third-party deps (element-ui's vue-popper, etc.) still use
            // require() internally — Vite's default build only transforms
            // require() it sees at dep pre-bundling time, not everything
            // Rollup encounters, so this needs to be explicit here.
            // core-js@2 (pulled in transitively by element-ui's async-validator
            // -> babel-runtime) is an IE-era polyfill set that this plugin's
            // interop mangles (`x.concat is not a function` at runtime) — Vite's
            // own default browser targets don't need it, so exclude it here
            // rather than trying to make the commonjs transform handle it.
            plugins: [commonjs({
                transformMixedEsModules: true,
                requireReturnsDefault: 'auto',
                exclude: ['**/core-js/**', '**/core-js-compat/**'],
            })],
        },
    },
});
