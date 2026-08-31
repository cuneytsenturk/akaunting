const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { pickFirstFromOpenDropdown } = require('./support/document-form');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

test('creates a bill for an existing vendor and item', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/purchases/bills/create`);
    await page.waitForLoadState('networkidle');

    await page.getByText('Add a Vendor').click();
    await pickFirstFromOpenDropdown(page);

    await page.getByText('Add an Item').click();
    await pickFirstFromOpenDropdown(page);

    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Bill created!')).toBeVisible({ timeout: 15000 });
    await expect(page.getByRole('heading', { name: /^Bill:/ })).toBeVisible();
});
