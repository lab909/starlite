import { expect, test } from './fixtures.js';

test('the browser downloads a small modern version of an image, not the original', async ({ page }) => {
    const images = [];
    page.on('response', (response) => {
        if (response.request().resourceType() === 'image' && response.url().includes('/media/')) {
            images.push([new URL(response.url()).pathname, response.headers()['content-type'], response.status()]);
        }
    });

    await page.goto('/blog/hello-starlite');
    await expect(page.locator('article picture img')).toBeVisible();

    expect(images.length).toBeGreaterThan(0);
    for (const [path, type, status] of images) {
        expect(status, path).toBe(200);
        expect(path, 'a responsive version').toMatch(/\.\d+w\.(avif|webp)$/);
        expect(type).toMatch(/^image\/(avif|webp)$/);
    }
});
