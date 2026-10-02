<?php

declare(strict_types=1);

namespace App\Controller;

use Spatie\SchemaOrg\Schema;
use Starlite\Blog\PostSeo;
use Starlite\Controller;
use Symfony\Component\HttpFoundation\Response;

final class BlogController extends Controller
{
    public function index(): string
    {
        $seo = $this->app->seo->title('Blog')->description('Articles about ' . $this->app->seo->site['name'] . '.');
        $seo->schema(Schema::blog()->name($seo->pageTitle())->url($seo->canonicalUrl()));

        return $this->render('blog/index.twig');
    }

    public function show(string $slug): string|Response
    {
        $post = $this->app->blog->find($slug);
        if ($post === null) {
            return $this->notFound('Post not found.');
        }
        PostSeo::apply($this->app->seo, $post);

        return $this->render('blog/post.twig', ['post' => $post]);
    }
}
