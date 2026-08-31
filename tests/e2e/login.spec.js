const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD } = require('./support/constants');

test.describe('Login', () => {
    test('logs in with valid credentials and reaches the dashboard', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        await expect(page).not.toHaveURL(/\/auth\/login/);
        await expect(page.getByRole('link', { name: /Dashboard/ }).first()).toBeVisible();
    });

    test('rejects invalid credentials and stays on the login page', async ({ page }) => {
        await page.goto('/auth/login');
        await page.waitForLoadState('networkidle');
        await page.fill('#email', ADMIN_EMAIL);
        await page.fill('#password', 'not-the-right-password');
        await page.click('button[type=submit]');

        await expect(page.locator('.bg-red-100.text-red-600')).toBeVisible({ timeout: 10000 });
        await expect(page).toHaveURL(/\/auth\/login/);
    });
});
