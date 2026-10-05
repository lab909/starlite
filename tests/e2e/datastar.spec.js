import { expect, test } from './fixtures.js';

test.beforeEach(async ({ page }) => {
    await page.goto('/');
});

test('search renders a partial as you type', async ({ page }) => {
    await page.getByPlaceholder('Search…').fill('ap');

    await expect(page.locator('#results li').first()).toBeVisible();
});

test('a signed template action patches the page and a signal', async ({ page }) => {
    await page.getByRole('button', { name: 'Like mango' }).click();

    await expect(page.locator('#likes')).toContainText('You liked Mango');
    await expect(page.getByText('Thanks for the like!')).toBeVisible();
});

test('a custom route streams server-sent events', async ({ page }) => {
    await page.getByRole('button', { name: 'What time is it?' }).click();

    await expect(page.locator('#clock')).toHaveText(/Server time: \d\d:\d\d:\d\d/);
});

test('blog search and tags filter the posts', async ({ page }) => {
    await page.goto('/blog');
    await page.getByPlaceholder('Search posts…').fill('markdown');
    await expect(page.locator('#blog-posts article')).toHaveCount(1);

    await page.getByPlaceholder('Search posts…').fill('');
    await page.locator('button', { hasText: 'starlite' }).click();
    await expect(page.locator('#blog-posts article')).toHaveCount(1);
});
