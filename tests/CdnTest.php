<?php

declare(strict_types=1);

namespace App\Tests;

/**
 * With CDN caching on (CDN_CACHE=1), this site's pages are kept at the CDN, except the ones that
 * must reach the server every time.
 */
final class CdnTest extends AppTestCase
{
    public function testPagesAreKeptAtTheCdnButNotTheContactForm(): void
    {
        $app = $this->app(overrides: ['content_dir' => self::ROOT . '/content', 'cdn' => ['enabled' => true]]);

        foreach (['/', '/blog', '/about', '/it/chi-siamo'] as $page) {
            self::assertStringContainsString('s-maxage=', (string) $this->request($app, $page)->headers->get('Cache-Control'), $page);
        }
        self::assertSame('no-store, private', $this->request($app, '/contact')->headers->get('Cache-Control'), 'its spam check token is new every time');
    }
}
