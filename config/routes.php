<?php

declare(strict_types=1);

use App\Controller\BlogController;
use App\Controller\ClockController;
use App\Controller\HomeController;
use App\Controller\PageController;
use Starlite\Blog\AssetController;
use Starlite\Blog\Blog;
use Starlite\Blog\FeedController;
use Starlite\Kernel;
use Starlite\Pages\AssetController as PageAssetController;
use Starlite\Pages\Pages;
use Starlite\Seo\RobotsController;
use Starlite\Seo\SitemapController;

// Handlers: [Controller::class, 'method'], an invokable Controller::class, or a closure.
return static function (Kernel $app): void {
    // Pages
    $app->get('/', HomeController::class, 'home');
    $app->get('/blog', [BlogController::class, 'index'], 'blog');
    $app->get('/blog/page/{page}', [BlogController::class, 'index'], 'blog_page', ['page' => '[1-9][0-9]*']);
    $app->get('/blog/{slug}', [BlogController::class, 'show'], 'blog_post', ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*']);
    // Post images and files. In production `deploy` copies them to public/, so this only runs in development.
    $app->get(Blog::ASSET_URL . '/{slug}/{file}', AssetController::class, 'blog_asset', ['file' => '.+']);

    // Feeds for crawlers and readers (framework controllers)
    $app->get('/blog/feed.xml', FeedController::class, 'blog_feed');
    $app->get('/sitemap.xml', SitemapController::class, 'sitemap');
    $app->get('/robots.txt', RobotsController::class, 'robots');

    // Datastar actions
    $app->post('/clock', ClockController::class, 'clock');

    // Content pages: content/pages/privacy/index.md is /privacy. A catch-all, so it has a low priority:
    // every other route wins, wherever it's added. `deploy` reports pages that a route hides.
    $app->get(Pages::ASSET_URL . '/{file}', PageAssetController::class, 'page_asset', ['file' => '.+']);
    $app->get('/{path}', [PageController::class, 'show'], 'page', ['path' => Pages::PATH], priority: -1);
    // Link to a page by its folder path, `path('page', {path: 'about'})`: Starlite writes each
    // language's URL, translated slugs included.
};
