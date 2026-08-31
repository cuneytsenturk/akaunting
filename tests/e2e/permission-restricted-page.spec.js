const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const {
    ADMIN_EMAIL, ADMIN_PASSWORD,
    ACCOUNTANT_EMAIL, ACCOUNTANT_PASSWORD,
    COMPANY_ID,
} = require('./support/constants');

test.describe('Permission boundaries', () => {
    test('a role without auth-users permission is forbidden from the Users page', async ({ page }) => {
        await login(page, ACCOUNTANT_EMAIL, ACCOUNTANT_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/auth/users`);

        expect(response.status()).toBe(403);
        await expect(page).toHaveTitle(/Forbidden Access/);
    });

    test('the admin role can reach the Users page', async ({ page }) => {
        await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

        const response = await page.goto(`/${COMPANY_ID}/auth/users`);

        expect(response.status()).toBe(200);
        await expect(page).toHaveTitle(/Users/);
    });
});
