<?php

declare(strict_types=1);

use App\Controller\BlogController;
use App\Controller\ClockController;
use App\Controller\HomeController;
use Starlite\Blog\FeedController;
use Starlite\Kernel;
use Starlite\Seo\RobotsController;
use Starlite\Seo\SitemapController;

// Handlers: [Controller::class, 'method'], an invokable Controller::class, or a closure.
return static function (Kernel $app): void {
    // Pages
    $app->get('/', HomeController::class, 'home');
    $app->get('/blog', [BlogController::class, 'index'], 'blog');
    $app->get('/blog/{slug}', [BlogController::class, 'show'], 'blog_post', ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*']);

    // Feeds for crawlers and readers (framework controllers)
    $app->get('/blog/feed.xml', FeedController::class, 'blog_feed');
    $app->get('/sitemap.xml', SitemapController::class, 'sitemap');
    $app->get('/robots.txt', RobotsController::class, 'robots');

    // Datastar actions
    $app->post('/clock', ClockController::class, 'clock');
};
