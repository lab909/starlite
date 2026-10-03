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
    // Comma-separated IPs/CIDRs of reverse proxies whose X-Forwarded-* headers are trusted ('REMOTE_ADDR' = the direct peer).
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) getenv('APP_TRUSTED_PROXIES'))))),
    'blog' => [
        // Posts per page on /blog. Set BLOG_PER_PAGE=1 in .env to try pagination with only a few posts.
        'per_page' => max(1, (int) (getenv('BLOG_PER_PAGE') ?: 20)),
    ],
    // Site-wide SEO defaults; pages override them through `seo` (see README, "SEO").
    'site' => [
        'name' => 'Starlite',
        'description' => 'A database-free PHP micro framework for static-like dynamic sites.',
        'locale' => 'en_US',
        'image' => null,   // default share image, e.g. '/images/og-default.png'
        'author' => null,  // used for blog posts and the feed; defaults to the site name
    ],
];
