const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { PORTAL_EMAIL, PORTAL_PASSWORD, COMPANY_ID } = require('./support/constants');

// Portal domain (Vite POC, see vite.config.js) — portal/apps.min.js is the
// single Vite-built entry, covering the client (customer-facing) portal:
// invoices, payments, profile. Uses a dedicated fixture user (role
// "customer", linked Contact) seeded by tests/e2e/support/bootstrap-test-data.php
// — the admin fixture used elsewhere lacks the read-client-portal permission
// and 403s on these routes.
test.describe('Portal domain', () => {
    test('invoices index renders for the portal customer', async ({ page }) => {
        await login(page, PORTAL_EMAIL, PORTAL_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/portal/invoices`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/Invoices/);
    });

    test('payments index renders for the portal customer', async ({ page }) => {
        await login(page, PORTAL_EMAIL, PORTAL_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/portal/payments`);
        await page.waitForLoadState('networkidle');

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/Payments/);
    });

    test('profile update saves a real field via the Vite-built portal/apps.js form', async ({ page }) => {
        await login(page, PORTAL_EMAIL, PORTAL_PASSWORD);

        await page.goto(`/${COMPANY_ID}/portal/profile`);
        await page.waitForLoadState('networkidle');

        const phone = `555${Date.now().toString().slice(-7)}`;
        await page.locator('input[name="phone"]').fill(phone);
        await page.getByRole('button', { name: 'Save', exact: true }).click();

        // The update response includes a `redirect` back to the same edit
        // page (see Portal\Profile::update()), which form.js's onSuccess()
        // follows via window.location.href automatically — wait for that
        // real navigation instead of racing it with a manual page.goto().
        await page.waitForURL(/\/portal\/profile/, { timeout: 15000 });
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[name="phone"]')).toHaveValue(phone);
    });
});
