const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

/**
 * Regression check for the webpack-to-vite-roadmap.md POC — this view's JS
 * bundle (public/js/banking/accounts.min.js) is built by Vite instead of Mix
 * (see vite.config.js), everything else in the app is still Mix-built.
 *
 * Getting the create flow working under Vite took 4 real fixes beyond the 3
 * the roadmap's isolated (build-only, never-opened-in-a-browser) POC found:
 *   1. AkauntingModal <-> AkauntingSelect <-> AkauntingSelectRemote <->
 *      AkauntingModalAddNew formed several circular imports. Webpack/CommonJS
 *      tolerates these; Rollup's stricter ESM evaluation order throws
 *      "Cannot access 'X' before initialization" (TDZ). Some edges were dead
 *      code (removed); the real one (AkauntingModalAddNew, actually used) was
 *      converted to a dynamic import() to break the cycle.
 *   2. require('./../../bootstrap') (and bootstrap.js's own require('lodash')/
 *      require('axios'), and functions.js's require('mathjs')) were never
 *      transformed by Vite's build — only dev-time optimizeDeps rewrites
 *      require(), not arbitrary require() calls reached during `vite build`.
 *      Converted to import statements.
 *   3. Third-party CJS internals (element-ui's vue-popper) still needed
 *      @rollup/plugin-commonjs explicitly — Vite's default build doesn't
 *      apply it project-wide. Once added, its interop broke on core-js@2
 *      (pulled in transitively via element-ui -> async-validator ->
 *      babel-runtime) with "x.concat is not a function" — excluded core-js
 *      from the commonjs transform since modern browser targets don't need
 *      an IE-era polyfill anyway.
 *   4. accounts.js mounts with `el: '#main-body'` and no template/render
 *      option, i.e. it compiles the server-rendered innerHTML as its
 *      template at runtime — that needs Vue's full build (compiler
 *      included). Vite's default "vue" resolution is the runtime-only
 *      build; the mount silently no-ops (no thrown error, just empty
 *      content) instead of failing loudly. Aliased vue -> vue/dist/vue.esm.js.
 *
 * None of these 4 were visible from a successful `vite build` alone — every
 * one only showed up by actually loading the page in a browser and
 * interacting with it, which is exactly why this suite exists.
 */
test.describe('Accounts view (Vite POC)', () => {
    test('index renders and lists accounts under the Vite-built bundle', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/banking/accounts`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/Accounts/);
        await expect(page.getByRole('link', { name: 'New Account' })).toBeVisible();

        // The seeded "Cash" account (see sample-data:seed) should be listed —
        // proves the Vue app actually mounted and fetched/rendered real data,
        // not just that the static shell loaded.
        await expect(page.getByText('Cash', { exact: true }).first()).toBeVisible();
    });

    test('creates a new account end to end', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        await page.goto(`/${COMPANY_ID}/banking/accounts/create`);
        await page.waitForLoadState('networkidle');

        const accountName = `E2E Vite Account ${Date.now()}`;
        await page.locator('input[name="name"]').fill(accountName);
        await page.locator('input[name="number"]').fill('700700');
        await page.getByRole('button', { name: 'Save', exact: true }).click();

        await page.waitForURL(/\/banking\/accounts\/\d+$/, { timeout: 15000 });
        await expect(page.getByRole('heading', { name: accountName })).toBeVisible();
    });
});
