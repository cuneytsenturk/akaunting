const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { pickFirstFromOpenDropdown } = require('./support/document-form');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Relies on fixture data from `sample-data:seed` (see tests/e2e/README.md) —
// picks whichever customer/item that seeder created rather than a fixed name.
test('creates an invoice for an existing customer and item', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/sales/invoices/create`);
    await page.waitForLoadState('networkidle');

    await page.getByText('Add a Customer').click();
    await pickFirstFromOpenDropdown(page);

    await page.getByText('Add an Item').click();
    await pickFirstFromOpenDropdown(page);

    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Invoice created!')).toBeVisible({ timeout: 15000 });
    await expect(page.getByRole('heading', { name: /^Invoice:/ })).toBeVisible();
});
