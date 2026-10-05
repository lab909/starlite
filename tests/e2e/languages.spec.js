import { expect, test } from './fixtures.js';

test('the language switcher goes to the translated URL', async ({ page }) => {
    await page.goto('/about');
    await page.getByRole('link', { name: 'Italiano' }).click();

    await expect(page).toHaveURL(/\/it\/chi-siamo$/);
    await expect(page.locator('h1')).toHaveText('Chi siamo');
    await expect(page.locator('html')).toHaveAttribute('lang', 'it');
});

test('an untranslated URL redirects to the translated one', async ({ page }) => {
    await page.goto('/it/about/credits');

    await expect(page).toHaveURL(/\/it\/chi-siamo\/riconoscimenti$/);
    await expect(page.locator('h1')).toHaveText('Riconoscimenti');
});

test('links in the Italian blog use the Italian slugs', async ({ page }) => {
    await page.goto('/it/blog');
    await page.getByRole('link', { name: 'Ciao, Starlite' }).click();

    await expect(page).toHaveURL(/\/it\/blog\/ciao-starlite$/);
});
