import { expect, test } from './fixtures.js';

// Click-to-load: nothing reaches the video host before the visitor presses play. Requests to other
// hosts are recorded and answered empty (no real network in tests).
test('the video loads from YouTube only after pressing play', async ({ page }) => {
    const thirdParty = [];
    await page.route((url) => url.hostname !== '127.0.0.1', (route) => {
        thirdParty.push(route.request().url());
        return route.fulfill({ status: 204 });
    });

    await page.goto('/blog/markdown-cheatsheet');
    const player = page.locator('figure iframe');
    await expect(player).toBeHidden();
    await expect(player).not.toHaveAttribute('src');
    expect(thirdParty).toEqual([]);

    await page.getByRole('link', { name: /Play video/ }).click();

    await expect(player).toBeVisible();
    await expect(player).toHaveAttribute('src', /^https:\/\/www\.youtube-nocookie\.com\/embed\/aqz-KE-bpKQ/);
    await expect.poll(() => thirdParty.some((url) => url.startsWith('https://www.youtube-nocookie.com/'))).toBe(true);
});
