const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Auth domain (Vite POC, see vite.config.js) — auth/common.min.js (guest pages:
// login, forgot password) and auth/users.min.js (users index/create/edit) are
// Vite-built.
//
// Note: the "Invite User" full submit flow is NOT exercised end-to-end here.
// It is blocked in this local sandbox by a pre-existing, environment-only bug
// unrelated to Vite: CreateUser::handle() (app/Jobs/Auth/CreateUser.php)
// dispatches CreateInvitation *inside* its own DB::transaction — when the
// invitation email fails to send (no working local mail transport is
// configured), CreateInvitation rethrows, which rolls back the whole
// transaction (including the just-created user row) and redirects back to
// the create page with a generic error flash. Confirmed identical under a
// fresh Mix build of users.js (same rollback, same redirect, no user
// persisted) — so this is out of scope for the migration. The test below
// instead exercises the real onChangeRole AJAX interaction (Role select ->
// GET /auth/users/landingpages -> Landing Page select populated) without
// hitting Save, which is the actual users.js-specific behavior worth
// locking in.
test.describe('Auth domain', () => {
    test('users index lists the admin user', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/auth/users`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/Users/);
        await expect(page.getByRole('cell', { name: ADMIN_EMAIL })).toBeVisible();
    });

    test('invite user form: selecting a role populates landing pages via AJAX', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        await page.goto(`/${COMPANY_ID}/auth/users/create`);
        await page.waitForLoadState('networkidle');

        await page.locator('input[placeholder="- Select Role -"]').click();
        await page.waitForTimeout(500);

        const landingPagesResponse = page.waitForResponse(res =>
            res.url().includes('/auth/users/landingpages') && res.request().method() === 'GET'
        );
        await page.locator('.el-select-dropdown:visible li').first().click();
        await landingPagesResponse;
        await page.waitForTimeout(300);

        const landingPages = await page.evaluate(() => {
            const app = document.getElementById('app');
            const vm = app && app.__vue__;
            return vm ? vm.landing_pages : null;
        });

        expect(landingPages).not.toBeNull();
        expect(Object.keys(landingPages).length).toBeGreaterThan(0);
    });

    test('profile edit page loads with the current user pre-filled', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/auth/profile/1/edit`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page.locator('input[name="email"]')).toHaveValue(ADMIN_EMAIL);
    });

    test('forgot password page renders (guest, auth/common.js)', async ({ page }) => {
        const response = await page.goto('/auth/forgot');
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page.locator('input[name="email"]')).toBeVisible();
    });
});
