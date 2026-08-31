const { defineConfig } = require('@playwright/test');

/**
 * E2E config for Akaunting core. Assumes a fully installed instance is
 * already running (see tests/e2e/README.md) — this suite does not boot
 * the Laravel app itself, only exercises it as a browser would.
 */
module.exports = defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['html', { open: 'never' }], ['list']],
    globalSetup: require.resolve('./tests/e2e/global-setup.js'),
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'chromium', use: { browserName: 'chromium' } },
    ],
});
