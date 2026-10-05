import { expect, test } from './fixtures.js';

// Every URL in the sitemap (both languages, posts and content pages) loads cleanly.
test('every page in the sitemap loads without errors', async ({ page, request, baseURL }) => {
    const sitemap = await (await request.get('/sitemap.xml')).text();
    const paths = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map(([, url]) => new URL(url).pathname);
    expect(paths.length).toBeGreaterThan(8);

    for (const path of paths) {
        const response = await page.goto(path);
        expect(response?.status(), path).toBe(200);
        await expect(page.locator('h1').first(), path).toBeVisible();
    }
    expect(baseURL).toContain('127.0.0.1');
});

test('an unknown URL is a 404 page', async ({ page, problems }) => {
    const response = await page.goto('/nope/missing');

    expect(response?.status()).toBe(404);
    await expect(page.getByText('Page not found.')).toBeVisible();
    // The browser logs the page's own 404 as a console error: expected here, and the only one allowed.
    const expected = problems.findIndex((problem) => problem.includes('status of 404'));
    expect(expected).toBeGreaterThanOrEqual(0);
    problems.splice(expected, 1);
});
