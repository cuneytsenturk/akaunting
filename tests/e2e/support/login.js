/**
 * Logs in via the real login form. Akaunting's login submit is handled by
 * Vue (plugins/form.js) which intercepts the native submit and redirects via
 * window.location — a plain waitForNavigation races that redirect, so we
 * poll the URL instead.
 */
async function login(page, email, password) {
    await page.goto('/auth/login');
    await page.waitForLoadState('networkidle');
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.click('button[type=submit]');

    for (let i = 0; i < 20; i++) {
        await page.waitForTimeout(500);
        if (!page.url().includes('/auth/login')) {
            return;
        }
    }

    throw new Error('Login did not redirect away from /auth/login within 10s');
}

module.exports = { login };
