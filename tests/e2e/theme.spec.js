import { expect, test } from './fixtures.js';

// What <html data-theme> is when the body first exists: what the first paint uses.
async function recordFirstPaint(page) {
    await page.addInitScript(() => document.addEventListener('DOMContentLoaded', () => {
        window.firstPaint = document.documentElement.dataset.theme;
    }));
}

test.use({ colorScheme: 'dark' });

test('follows the system by default', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
});

test('a chosen theme is remembered from the very first paint, without a flash', async ({ page }) => {
    await recordFirstPaint(page);
    await page.goto('/');
    await page.getByTitle('Light theme').click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');

    await page.reload();
    expect(await page.evaluate(() => window.firstPaint)).toBe('light');
    await expect(page.getByRole('radio', { name: 'Light theme' })).toBeChecked();
});

test("'system' follows the operating system as it switches", async ({ page }) => {
    await page.goto('/');
    await page.getByTitle('System theme').click();

    await page.emulateMedia({ colorScheme: 'light' });
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
    await page.emulateMedia({ colorScheme: 'dark' });
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
});
