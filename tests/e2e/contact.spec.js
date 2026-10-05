import { expect, test } from './fixtures.js';

// The timing spam check refuses forms sent within 3 seconds of being shown: wait like a person would.
const humanPause = 3200;

async function fillIn(page, message = 'Hello, a question about Starlite.') {
    await page.getByLabel('Name').fill('Ada Lovelace');
    await page.getByLabel('Email').fill('ada@example.test');
    await page.getByLabel('Message').fill(message);
    await page.waitForTimeout(humanPause);
}

test('the contact form sends without a reload', async ({ page }) => {
    await page.goto('/contact');
    await fillIn(page);
    await page.getByRole('button', { name: 'Send' }).click();

    await expect(page.getByRole('status')).toHaveText('Thanks! Your message is on its way.');
    expect(new URL(page.url()).search).toBe(''); // updated in place by Datastar
});

test('a problem found on the server is shown in the form', async ({ page }) => {
    await page.goto('/contact');
    await fillIn(page, 'See https://a.test, https://b.test and https://c.test');
    await page.getByRole('button', { name: 'Send' }).click();

    await expect(page.getByRole('alert')).toHaveText('Please include at most 2 links.');
    await expect(page.getByLabel('Message')).toHaveValue('See https://a.test, https://b.test and https://c.test');
});

test.describe('without JavaScript', () => {
    test.use({ javaScriptEnabled: false });

    test('the form is a normal post, and a refresh cannot send it twice', async ({ page }) => {
        await page.goto('/it/contatti');
        await page.getByLabel('Nome').fill('Ada Lovelace');
        await page.getByLabel('Email').fill('ada@example.test');
        await page.getByLabel('Messaggio').fill('Ciao, una domanda su Starlite.');
        await page.waitForTimeout(humanPause);
        await page.getByRole('button', { name: 'Invia' }).click();

        await expect(page).toHaveURL(/\/it\/contatti\?sent=1$/);
        await expect(page.getByRole('status')).toHaveText('Grazie! Il tuo messaggio è in viaggio.');
    });
});
