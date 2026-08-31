const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Settings domain (Vite POC, see vite.config.js) — settings.min.js is
// Vite-built and backs all of Company/Localisation/Invoice/Default/Email/
// Email Templates/Scheduling under one entry (see webpack.mix.js).
test('saves the localisation settings form unchanged', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/settings/localisation`);
    await page.waitForLoadState('networkidle');

    await page.getByRole('button', { name: 'Save', exact: true }).click();

    await expect(page.getByText('Settings updated!')).toBeVisible({ timeout: 15000 });
});
