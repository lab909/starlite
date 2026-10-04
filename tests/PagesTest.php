<?php

declare(strict_types=1);

namespace App\Tests;

use Starlite\Theme;

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

    public function testPageScriptsLoadOnlyWhereTheyAreUsed(): void
    {
        $app = $this->app();

        // Built bundles: the manifest maps the entries to hashed files (run `npm run build` first).
        self::assertMatchesRegularExpression('#<script type="module" src="/build/assets/home-[^"]+\.js"></script>#', self::body($this->request($app, '/')));
        self::assertStringNotContainsString('/build/assets/home-', self::body($this->request($app, '/blog')));
    }

    public function testThemeIsAppliedBeforeAnythingElseInTheHead(): void
    {
        $html = self::body($this->request($this->app(), '/'));

        // First in <head> after the meta tags, before any stylesheet: no flash of the wrong theme.
        self::assertMatchesRegularExpression('#<meta name="viewport"[^>]*>\s*<script>\(function\(\)\{var t;try\{t=JSON\.parse\(localStorage#', $html);
        self::assertLessThan(strpos($html, 'rel="stylesheet"') ?: strpos($html, '<script type="module"'), strpos($html, Theme::SCRIPT));
        foreach (['light', 'dark', 'system'] as $theme) {
            self::assertStringContainsString('<input type="radio" name="theme" value="' . $theme . '" class="sr-only" data-bind:_theme>', $html);
        }
    }

    public function testTheFontIsSelfHostedAndPreloaded(): void
    {
        $html = self::body($this->request($this->app(), '/'));

        self::assertMatchesRegularExpression('#<link rel="preload" href="/build/assets/inter-latin-wght-normal-[^"]+\.woff2" as="font" type="font/woff2" crossorigin>#', $html);
        self::assertStringNotContainsString('fonts.googleapis.com', $html);
    }
}
