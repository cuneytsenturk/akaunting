const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Settings domain (Vite POC, see vite.config.js) — categories.min.js is Vite-built.
test('creates a category', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/settings/categories/create`);
    await page.waitForLoadState('networkidle');

    const name = `E2E Vite Category ${Date.now()}`;
    await page.locator('input[name="name"]').fill(name);

    // Type is a required <el-select> with no sensible default — any option works.
    await page.locator('input[placeholder="- Select Type -"]').click();
    await page.waitForTimeout(500);
    await page.locator('.el-select-dropdown:visible li').first().click();

    await page.getByRole('button', { name: 'Save', exact: true }).click();

    // Category creation redirects to the index list, not a numbered show page.
    await page.waitForURL(new RegExp(`/${COMPANY_ID}/settings/categories$`), { timeout: 15000 });
    await expect(page.getByText(name, { exact: true })).toBeVisible();
});
