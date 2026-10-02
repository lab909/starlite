<?php

declare(strict_types=1);

namespace App\Controller;

use Starlite\Controller;

final class BlogController extends Controller
{
    public function index(): string
    {
        return $this->render('blog/index.twig');
    }

    public function show(string $slug): ?string
    {
        $post = $this->app->blog->find($slug);

        return $post === null ? $this->notFound('Post not found.') : $this->render('blog/post.twig', ['post' => $post]);
    }
}
