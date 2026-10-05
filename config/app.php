<?php

// Secrets come from the environment or the gitignored .env file, never from the repo or the frontend bundle.
// Generate a secret with `openssl rand -hex 32`. See .env.example.
$url = rtrim((string) getenv('APP_URL'), '/');
if (!preg_match('#^https?://[^/\s]+(/\S*)?$#', $url)) {
    throw new RuntimeException('APP_URL must be the public base URL, e.g. https://example.com');
}

return [
    'secret' => getenv('APP_SECRET') ?: throw new RuntimeException('APP_SECRET is not set.'),
    'debug' => getenv('APP_DEBUG') === '1',
    // Public base URL: canonical links, Open Graph, sitemap and feed. Never derived from the Host header.
    'url' => $url,
    // Where post and page files (images, PDFs, videos) and video posters are served from: '' for this
    // site (/media/…), or a CDN that pulls them from it, e.g. https://cdn.example.com (MEDIA_URL).
    'media_url' => (string) getenv('MEDIA_URL'),
    // Comma-separated IPs/CIDRs of reverse proxies whose X-Forwarded-* headers are trusted ('REMOTE_ADDR' = the direct peer).
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) getenv('APP_TRUSTED_PROXIES'))))),
    // Default language: served without a URL prefix (/blog). Every other language in `languages`
    // gets its code as prefix (/it/blog). An Italian-only site: 'language' => 'it' and list only 'it';
    // add 'en' later and English appears under /en/. UI texts live in translations/<code>.php.
    'language' => 'en',
    'languages' => [
        'en' => ['name' => 'English', 'locale' => 'en_US'],
        'it' => ['name' => 'Italiano', 'locale' => 'it_IT'],
    ],
    'blog' => [
        // Posts per page on /blog. Set BLOG_PER_PAGE=1 in .env to try pagination with only a few posts.
        'per_page' => max(1, (int) (getenv('BLOG_PER_PAGE') ?: 20)),
    ],
    // Values page scripts may read (`{{ public_config() }}` in the layout, `publicConfig()` in JS). This is
    // an allowlist: nothing else from this file reaches the browser. Everything here is public, so never
    // put secrets or private URLs in it (a value containing APP_SECRET is refused).
    // E.g. 'public' => ['sounds_url' => getenv('SOUNDS_URL') ?: '/sounds'],
    'public' => [],
    // Content Security Policy: browsers only load scripts, styles, fonts, images, frames… from your own
    // site, plus the hosts listed here per directive. Add what a feature needs, e.g. a video player:
    //   'frame-src' => ['https://www.youtube-nocookie.com'], 'media-src' => ['https://cdn.example.com'],
    // report_only: browsers report violations in the console without blocking (to try a change first).
    'csp' => [
        'enabled' => true,
        'report_only' => false,
        'sources' => [],
    ],
    // Site-wide SEO defaults; pages override them through `seo` (see README, "SEO").
    'site' => [
        'name' => 'Starlite',
        'description' => 'A database-free PHP micro framework for static-like dynamic sites.',
        'image' => null,   // default share image, e.g. '/images/og-default.png'
        'author' => null,  // used for blog posts and the feed; defaults to the site name
    ],
];
