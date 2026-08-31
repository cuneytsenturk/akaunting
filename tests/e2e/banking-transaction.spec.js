const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Uses the first account created by `sample-data:seed` (account id 1) and its
// account/category/payment-method defaults — only amount + description are filled.
test('creates an expense transaction against an account', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/banking/accounts/1/create-expense`);
    await page.waitForLoadState('networkidle');

    await page.locator('input[name="amount"]').fill('50');
    await page.locator('textarea[name="description"]').fill('E2E test expense transaction');

    await page.getByRole('button', { name: 'Save', exact: true }).click();

    // Assert on the resulting page rather than the success toast — the toast
    // auto-dismisses and can already be gone by the time we get to check it.
    await page.waitForURL(/\/banking\/transactions\/\d+$/, { timeout: 15000 });
    await expect(page.getByText('E2E test expense transaction')).toBeVisible();
});
