<?php

declare(strict_types=1);

namespace App\Controller;

use Spatie\SchemaOrg\Schema;
use Starlite\Blog\PostSeo;
use Starlite\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class BlogController extends Controller
{
    /** /blog is page 1; /blog/page/2 onwards are the older posts. */
    public function index(string $page = '1'): string|Response
    {
        $page = (int) $page;
        if ($page === 1 && $this->request()->getPathInfo() !== '/blog') {
            return new RedirectResponse($this->app->router->generate('blog'), Response::HTTP_MOVED_PERMANENTLY);
        }
        $result = $this->app->blog->page($page);
        if ($page > $result['pages']) {
            return $this->notFound('Page not found.');
        }

        $seo = $this->app->seo
            ->title($page === 1 ? 'Blog' : "Blog · Page {$page}")
            ->description('Articles about ' . $this->app->seo->site['name'] . '.');
        $seo->schema(Schema::blog()->name($seo->pageTitle())->url($seo->canonicalUrl()));

        return $this->render('blog/index.twig', ['result' => $result]);
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
