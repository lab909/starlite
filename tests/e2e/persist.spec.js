import { expect, test } from './fixtures.js';

// A persisted signal must win over the page's default after a reload. (The demo module waits for
// `ready` itself; persist()'s own wait, for callers that don't, is what theme.spec.js relies on:
// theme() calls persist() straight away.)
test('a persisted signal survives a reload, over the page default', async ({ page }) => {
    await page.goto('/');
    const volume = page.locator('input[type=range]');
    await expect(volume).toHaveValue('0.5');

    await volume.fill('0.8');
    await page.reload();

    await expect(volume).toHaveValue('0.8');
    await expect(page.getByText('80%')).toBeVisible();
});
