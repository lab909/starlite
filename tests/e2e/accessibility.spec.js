import { expect, test } from './fixtures.js';

test('the skip link comes first and moves the focus to the content', async ({ page }) => {
    await page.goto('/blog');
    const skip = page.getByRole('link', { name: 'Skip to content' });
    await expect(skip).not.toBeInViewport(); // visually hidden until focused

    await page.keyboard.press('Tab');
    await expect(skip).toBeFocused();
    await expect(skip).toBeInViewport();

    await page.keyboard.press('Enter');
    await expect(page.locator('main')).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.locator('main a, main input, main button').first()).toBeFocused(); // past the navigation
});

test('keyboard focus is always visible', async ({ page }) => {
    await page.goto('/');
    await page.keyboard.press('Tab'); // skip link
    await page.keyboard.press('Tab'); // the site name

    const outline = await page.evaluate(() => getComputedStyle(document.activeElement).outlineStyle);
    expect(outline).toBe('solid');
});

test('reduced motion turns transitions off', async ({ page }) => {
    // The video's play button: the element with a transition (whether or not the poster was downloaded,
    // which CI never does: without it, a plain placeholder comes first in the link).
    const duration = async () => page.evaluate(() => parseFloat(getComputedStyle(document.querySelector('figure .transition')).transitionDuration));

    await page.goto('/blog/markdown-cheatsheet');
    expect(await duration()).toBeGreaterThan(0.1);

    await page.emulateMedia({ reducedMotion: 'reduce' });
    expect(await duration()).toBeLessThan(0.001);
});
