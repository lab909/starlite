<?php

declare(strict_types=1);

use App\Controller\BlogController;
use App\Controller\ClockController;
use App\Controller\HomeController;
use Starlite\Kernel;

// Handlers: [Controller::class, 'method'], an invokable Controller::class, or a closure.
return static function (Kernel $app): void {
    // Pages
    $app->get('/', HomeController::class, 'home');
    $app->get('/blog', [BlogController::class, 'index'], 'blog');
    $app->get('/blog/{slug}', [BlogController::class, 'show'], 'blog_post', ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*']);

    // Datastar actions
    $app->post('/clock', ClockController::class, 'clock');
};
