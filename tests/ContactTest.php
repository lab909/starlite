<?php

declare(strict_types=1);

namespace App\Tests;

/**
 * The contact page (content/pages/contact/, config/forms.php "contact"). Sending itself is tested in
 * the framework and in the browser tests: here, posts come back before any email (errors, or spam).
 */
final class ContactTest extends AppTestCase
{
    private const VALID = ['name' => 'Ada', 'email' => 'ada@example.test', 'message' => 'Hello, a question about Starlite.'];

    /**
     * @param array<string, string> $data
     * @param array<string, string> $headers
     */
    private function post(string $uri, array $data, array $headers = []): \Symfony\Component\HttpFoundation\Response
    {
        return $this->request($this->app(overrides: ['content_dir' => self::ROOT . '/content']), $uri, 'POST', ['Sec-Fetch-Site' => 'same-origin'] + $headers, parameters: $data);
    }

    public function testThePageShowsTheFormAndIsNeverCached(): void
    {
        $response = $this->request($this->app(overrides: ['content_dir' => self::ROOT . '/content']), '/contact');
        $html = self::body($response);

        self::assertStringContainsString('<form id="contact-form" method="post" action="/contact"', $html);
        self::assertStringContainsString('name="website"', $html, 'honeypot');
        self::assertStringContainsString('name="_token"', $html, 'timing token');
        self::assertSame('no-store, private', $response->headers->get('Cache-Control'));
    }

    public function testErrorsInThePagesLanguage(): void
    {
        $response = $this->post('/contact', ['email' => 'nope', 'message' => 'Hi']);
        $html = self::body($response);
        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('This field is required.', $html);
        self::assertStringContainsString('Enter a valid email address.', $html);
        self::assertStringContainsString('Use at least 10 characters.', $html);
        self::assertStringContainsString('value="nope"', $html, 'what was typed is kept');

        $italian = self::body($this->post('/it/contatti', ['email' => 'nope']));
        self::assertStringContainsString('Campo obbligatorio.', $italian);
        self::assertStringContainsString('Inserisci un indirizzo email valido.', $italian);
    }

    public function testDatastarGetsTheFormBackInPlace(): void
    {
        $response = $this->post('/contact', ['email' => 'nope'] + self::VALID, ['Datastar-Request' => 'true']);
        $events = self::body($response);

        self::assertStringStartsWith('text/event-stream', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('<form id="contact-form"', $events);
        self::assertStringContainsString('Enter a valid email address.', $events);
    }

    public function testABotIsToldItWorked(): void
    {
        // No timing token and a filled honeypot: rejected, but the bot can't tell.
        $response = $this->post('/contact', self::VALID + ['website' => 'https://spam.test']);
        self::assertStringContainsString('Form "contact" rejected as spam (honeypot).', $this->logged());

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/contact?sent=1', $response->headers->get('Location'));
        self::assertStringContainsString('Thanks! Your message is on its way.', self::body($this->request($this->app(overrides: ['content_dir' => self::ROOT . '/content']), '/contact?sent=1')));
    }

    public function testPagesWithoutAFormRefusePosts(): void
    {
        self::assertSame(405, $this->post('/privacy', self::VALID)->getStatusCode());
    }
}
