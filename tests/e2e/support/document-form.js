/**
 * Akaunting's contact/item pickers (resources/assets/js/components/AkauntingSearch.vue
 * and the invoice/bill item-row picker) both render as a `.is-open` panel with a
 * `<ul>` of `<div>` rows for existing records. Selecting the first row avoids
 * depending on the exact sample data a given environment happens to have.
 */
async function pickFirstFromOpenDropdown(page) {
    await page.locator('.is-open ul div').first().waitFor();
    await page.locator('.is-open ul div').first().click();
}

module.exports = { pickFirstFromOpenDropdown };
