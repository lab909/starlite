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

    public function testTheSitesOwnContentIsValid(): void
    {
        // The real content/, not the fixtures: a broken post or collection file fails here, in CI.
        $app = $this->app(overrides: ['content_dir' => self::ROOT . '/content']);
        self::assertTrue($app->posts()->exists());
        self::assertNotEmpty($app->collections->warmup());
        self::assertTrue($app->pages()->exists());
        self::assertSame([], $app->shadowedPages(), 'no content page hidden behind a route');

        self::assertMatchesRegularExpression('#What is Starlite\?.*Where does the content live\?.*How fast is it\?#s', self::body($this->request($app, '/')));
        self::assertMatchesRegularExpression('#Che cos&\#039;è Starlite\?.*Dove si trovano i contenuti\?.*Quanto è veloce\?#s', self::body($this->request($app, '/it')));
    }

    public function testContentPagesWithTheirTemplatesAndTheFooterMenu(): void
    {
        $app = $this->app(overrides: ['content_dir' => self::ROOT . '/content']);

        $about = self::body($this->request($app, '/about'));
        self::assertStringContainsString('<title>About · Starlite</title>', $about);
        self::assertStringContainsString('href="/about/credits"', $about, 'pages/about.twig lists the child pages');
        self::assertMatchesRegularExpression('#<footer.*href="/about".*href="/privacy".*</footer>#s', $about, 'menu in `order`');

        // Italian slugs (slug: in index.it.md): its own URLs, and the folder paths redirect there.
        $credits = self::body($this->request($app, '/it/chi-siamo/riconoscimenti'));
        self::assertStringContainsString('<h1 class="mb-8 text-4xl font-bold tracking-tight">Riconoscimenti</h1>', $credits);
        self::assertMatchesRegularExpression('#<a href="/it/chi-siamo"[^>]*>← Chi siamo</a>#', $credits, 'link to the parent page');
        self::assertStringContainsString('<link rel="alternate" hreflang="en" href="https://example.test/about/credits">', $credits);
        $moved = $this->request($app, '/it/about/credits');
        self::assertSame([301, '/it/chi-siamo/riconoscimenti'], [$moved->getStatusCode(), $moved->headers->get('Location')]);
        $post = $this->request($app, '/it/blog/hello-starlite');
        self::assertSame([301, '/it/blog/ciao-starlite'], [$post->getStatusCode(), $post->headers->get('Location')]);

        $missing = $this->request($app, '/nope');
        self::assertSame(404, $missing->getStatusCode());
        self::assertStringContainsString('Page not found.', self::body($missing));
    }

    public function testContentComponentsRenderInPosts(): void
    {
        // Production mode: a missing video poster is never downloaded during a request (only by deploy).
        $app = $this->app(overrides: ['content_dir' => self::ROOT . '/content']);

        // ::related-posts{limit=2} at the end of the post: templates/_components/related-posts.twig.
        $post = self::body($this->request($app, '/blog/hello-starlite'));
        self::assertMatchesRegularExpression('#<h2[^>]*>Related posts</h2>.*href="/blog/markdown-cheatsheet"#s', $post);
        self::assertStringNotContainsString('href="/blog/hello-starlite" class="hover:underline"', $post, 'not the post itself');

        // The cheatsheet shows the syntax in a code block: text, not a component.
        $cheatsheet = $this->request($app, '/blog/markdown-cheatsheet');
        self::assertStringContainsString('<code class="language-md">::related-posts{limit=2}', self::body($cheatsheet));

        // ::youtube, a framework default component: click-to-load, the player allowed on this page only.
        self::assertStringContainsString('data-src="https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ', self::body($cheatsheet));
        self::assertStringContainsString('frame-src', (string) $cheatsheet->headers->get('Content-Security-Policy'));
        self::assertStringNotContainsString('frame-src', (string) $this->request($app, '/blog/hello-starlite')->headers->get('Content-Security-Policy'));
    }
}
