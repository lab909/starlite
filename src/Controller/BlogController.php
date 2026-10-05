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
        // Languages can have different numbers of posts: page 3 may only exist in some of them.
        $alternates = $fallbacks = [];
        foreach (array_keys($this->app->site->languages) as $language) {
            if ($page <= $this->app->posts()->language($language)->paginate($page)['pages']) {
                $alternates[$language] = $this->path($page === 1 ? 'blog' : 'blog_page', $page === 1 ? [] : ['page' => $page], $language);
            } else {
                $fallbacks[$language] = $this->path('blog', [], $language);
            }
        }
        $this->app->site->setAlternates($alternates, $fallbacks);

        $result = $this->app->posts()->paginate($page);
        if ($page > $result['pages']) {
            return $this->notFound($this->t('Page not found.'));
        }

        $seo = $this->app->seo
            ->title($page === 1 ? $this->t('Blog') : $this->t('Blog · Page {page}', ['page' => $page]))
            ->description($this->t('Articles about {site}.', ['site' => $this->app->site->name()]));
        $seo->schema(Schema::blog()->name($seo->pageTitle())->url($seo->canonicalUrl()));

        return $this->render('blog/index.twig', ['result' => $result]);
    }

    /** @param string $slug the URL segment in the current language: `slug:` in a translation can change it */
    public function show(string $slug): string|Response
    {
        $post = $this->app->posts()->where('uri', $slug)->one();
        if ($post === null) {
            // Reached by its folder name, but this language has a translated slug: it moved.
            $moved = $this->app->posts()->slug($slug)->one();
            if ($moved !== null) {
                return new RedirectResponse($this->path('blog_post', ['slug' => $moved['slug']]), Response::HTTP_MOVED_PERMANENTLY);
            }
            // Not written in this language (or doesn't exist): 404, whose switcher links to the existing versions.
            $this->setPostAlternates($slug);

            return $this->notFound($this->t('Post not found.'));
        }
        $this->setPostAlternates($post['slug']);
        PostSeo::apply($this->app->seo, $post);

        return $this->render('blog/post.twig', ['post' => $post]);
    }

    /**
     * The switcher and hreflang offer only the languages a post is written in, each at its own URL
     * (path() writes it from the folder name); the others get their blog's front page.
     */
    private function setPostAlternates(string $slug): void
    {
        $alternates = $fallbacks = [];
        $translations = $this->app->blog->translations($slug);
        foreach (array_keys($this->app->site->languages) as $language) {
            if (in_array($language, $translations, true)) {
                $alternates[$language] = $this->path('blog_post', ['slug' => $slug], $language);
            } else {
                $fallbacks[$language] = $this->path('blog', [], $language);
            }
        }
        $this->app->site->setAlternates($alternates, $fallbacks);
    }
}
