const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Banking domain (Vite POC, see vite.config.js) — transfers.min.js is Vite-built.
// Uses "Cash" and "Quam hic et." — both from sample-data:seed and stable
// across runs; the dropdown list itself grows with every account created by
// other E2E tests, so picking by position (nth) is unreliable once the
// Element UI list virtualizes/scrolls.
test('creates a transfer between two accounts', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/banking/transfers/create`);
    await page.waitForLoadState('networkidle');

    // Both accounts are real <el-select> dropdowns — the visible "Cash"
    // pre-fill on "From Account" is display-only, the underlying value isn't
    // set until explicitly picked from the dropdown (confirmed: submitting
    // without doing this 422s with "the from account id field is required").
    await page.locator('#form-select-from_account_id .el-input__inner').click();
    await page.locator('#form-select-from_account_id .el-input__inner').fill('Cash');
    await page.waitForTimeout(500);
    await page.locator('.el-select-dropdown:visible li').filter({ hasText: 'Cash' }).first().click();

    // Type to filter — the account list grows with every account created by
    // other E2E tests, so the target item isn't always in the (custom,
    // non-native-scroll) dropdown's initially visible window.
    await page.locator('#form-select-to_account_id .el-input__inner').click();
    await page.locator('#form-select-to_account_id .el-input__inner').fill('Quam hic et.');
    await page.waitForTimeout(500);
    await page.locator('.el-select-dropdown:visible li').filter({ hasText: 'Quam hic et.' }).first().click();

    await page.locator('input[name="amount"]').fill('25');

    await page.getByRole('button', { name: 'Save', exact: true }).click();

    // Assert on the resulting page rather than the success toast — the toast
    // auto-dismisses and can already be gone by the time we get to check it.
    // Reaching the numbered show page is itself proof the transfer saved.
    await page.waitForURL(/\/banking\/transfers\/\d+$/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: 'Transfer', exact: true })).toBeVisible();
});
