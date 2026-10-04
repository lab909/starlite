<?php

declare(strict_types=1);

namespace App\Tests;

use Starlite\Kernel;
use Starlite\Testing\KernelTestCase;

/**
 * Boots this site (its config/, controllers and templates) with the fixture content in
 * tests/data/content, one post per page and a throw-away cache directory.
 * The templates include the Vite build: run `npm run build` once before the tests.
 */
abstract class AppTestCase extends KernelTestCase
{
    protected const ROOT = __DIR__ . '/..';

    /** @param array<string, mixed> $overrides */
    protected function app(bool $debug = false, array $overrides = []): Kernel
    {
        return $this->bootKernel(self::ROOT, $debug, array_replace_recursive([
            'content_dir' => __DIR__ . '/data/content',
            'blog' => ['per_page' => 1],
        ], $overrides));
    }

    /** The Datastar URL in the first `@get(...)` on a page whose signed config renders $template. */
    protected function datastarUrl(string $html, string $template): string
    {
        preg_match_all('#@get\(&quot;([^&]+(?:&amp;[^&]+)*)&quot;\)#', $html, $matches);
        foreach ($matches[1] as $url) {
            $url = html_entity_decode($url);
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $payload = explode('.', is_string($query['config'] ?? null) ? $query['config'] : '')[0];
            $config = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
            if (($config['t'] ?? null) === $template) {
                return $url;
            }
        }
        self::fail("No Datastar URL for {$template}.");
    }
}
