const { test, expect } = require('@playwright/test');
const { login } = require('./support/login');
const { ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_ID } = require('./support/constants');

// Settings domain (Vite POC, see vite.config.js) — currencies.min.js is Vite-built.
test('creates a currency', async ({ page }) => {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);

    await page.goto(`/${COMPANY_ID}/settings/currencies/create`);
    await page.waitForLoadState('networkidle');

    // Code is a searchable <el-select> — click to open, then type to filter
    // (the list is long and unfiltered options aren't guaranteed visible).
    // Random pick from a wide pool — code is unique per company and this
    // fixture DB accumulates currencies across repeated runs, so collisions
    // need to stay unlikely.
    const codes = [
        'EUR', 'GBP', 'CAD', 'AUD', 'NZD', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN',
        'CZK', 'HUF', 'RON', 'BGN', 'HRK', 'ISK', 'TRY', 'ZAR', 'MXN', 'BRL',
        'INR', 'SGD', 'HKD', 'KRW', 'THB', 'MYR', 'IDR', 'PHP', 'VND', 'ILS',
    ];
    const code = codes[Math.floor(Math.random() * codes.length)];
    await page.locator('input[placeholder="- Select Code -"]').click();
    await page.waitForTimeout(500);
    await page.keyboard.type(code);
    await page.waitForTimeout(500);
    await page.locator('.el-select-dropdown:visible li').first().click();

    // Selecting a code triggers an async GET to /settings/currencies/config
    // that auto-fills symbol/precision/decimal_mark/etc. — filling Name/Rate
    // before that resolves does not affect it (separate reactive fields),
    // but the field wasn't reliably ready before the underlying request
    // finished in manual testing, so give it a moment.
    await page.waitForTimeout(1500);

    const name = `E2E Vite Currency ${Date.now()}`;
    await page.locator('input[name="name"]').fill(name);
    await page.locator('input[name="rate"]').fill('0.9');

    await page.getByRole('button', { name: 'Save', exact: true }).click();

    // Currency creation redirects to the index list, not a numbered show page.
    await page.waitForURL(new RegExp(`/${COMPANY_ID}/settings/currencies$`), { timeout: 15000 });
    await expect(page.getByText(name, { exact: true })).toBeVisible();
});
