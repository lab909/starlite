<?php

declare(strict_types=1);

namespace App\Tests;

final class PagesTest extends AppTestCase
{
    public function testHomePageInBothLanguages(): void
    {
        $app = $this->app();

        self::assertStringContainsString('Datastar demo', self::body($this->request($app, '/')));
        self::assertStringContainsString('Demo Datastar', self::body($this->request($app, '/it')));
    }

    public function testClockActionStreamsTheServerTime(): void
    {
        $app = $this->app();
        $response = $this->request($app, '/clock', 'POST', ['Sec-Fetch-Site' => 'same-origin'], '{}');

        self::assertStringStartsWith('text/event-stream', (string) $response->headers->get('Content-Type'));
        self::assertMatchesRegularExpression('#<p id="clock">Server time: \d\d:\d\d:\d\d</p>#', self::body($response));
        self::assertStringContainsString('Ora del server:', self::body($this->request($app, '/it/clock', 'POST', ['Sec-Fetch-Site' => 'same-origin'], '{}')));
        self::assertSame(403, $this->request($app, '/clock', 'POST', ['Sec-Fetch-Site' => 'cross-site'])->getStatusCode());
    }

    public function testLanguageSwitcherInTheNavigation(): void
    {
        $html = self::body($this->request($this->app(), '/blog'));

        self::assertStringContainsString('<span aria-current="true" class="font-semibold">English</span>', $html);
        self::assertMatchesRegularExpression('#<a href="/it/blog" lang="it" hreflang="it"#', $html);
    }
}
