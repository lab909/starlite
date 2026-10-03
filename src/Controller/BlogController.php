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
    public function index(?string $page = null): string|Response
    {
        if ($page === '1') {
            return new RedirectResponse($this->path('blog'), Response::HTTP_MOVED_PERMANENTLY);
        }
        $page = (int) ($page ?? 1);
        $result = $this->app->blog->page($page);
        if ($page > $result['pages']) {
            return $this->notFound($this->t('Page not found.'));
        }

        $seo = $this->app->seo
            ->title($page === 1 ? $this->t('Blog') : $this->t('Blog · Page {page}', ['page' => $page]))
            ->description($this->t('Articles about {site}.', ['site' => $this->app->site->name]));
        $seo->schema(Schema::blog()->name($seo->pageTitle())->url($seo->canonicalUrl()));

        return $this->render('blog/index.twig', ['result' => $result]);
    }

    public function show(string $slug): string|Response
    {
        $post = $this->app->blog->find($slug);
        if ($post === null) {
            return $this->notFound($this->t('Post not found.'));
        }
        PostSeo::apply($this->app->seo, $post);

        return $this->render('blog/post.twig', ['post' => $post]);
    }
}
