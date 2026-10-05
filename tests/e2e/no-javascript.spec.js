import { expect, test } from './fixtures.js';

test.use({ javaScriptEnabled: false, colorScheme: 'dark' });

test('without JavaScript the site still works', async ({ page }) => {
    await page.goto('/blog/markdown-cheatsheet');

    // Colours follow the system (no data-theme), the theme switcher is hidden, the video is a link.
    expect(await page.locator('html').getAttribute('data-theme')).toBeNull();
    expect(await page.evaluate(() => getComputedStyle(document.body).backgroundColor)).not.toBe('rgb(255, 255, 255)');
    await expect(page.locator('fieldset')).toBeHidden();
    await expect(page.getByRole('link', { name: /Play video/ })).toHaveAttribute('href', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ');
});
