<?php

declare(strict_types=1);

namespace App\Controller;

use Starlite\Controller;
use Starlite\Pages\PageSeo;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content pages from content/pages/<path>/index.md (Privacy, About…). The route is a catch-all with
 * a low priority (config/routes.php), so every other route wins and unknown URLs end here as a 404.
 */
final class PageController extends Controller
{
    /** @param string $path the URL path in the current language (`slug:` in a translation can change it) */
    public function show(string $path): string|Response
    {
        $page = $this->app->pages()->where('uri', $path)->one();
        if ($page === null) {
            // Reached by its folder path, but this language has a translated slug (/it/about for /it/chi-siamo).
            $moved = $this->app->pages()->where('path', $path)->one();
            if ($moved !== null) {
                return new RedirectResponse($this->path('page', ['path' => $moved['path']]), Response::HTTP_MOVED_PERMANENTLY);
            }

            return $this->notFound($this->t('Page not found.'));
        }

        // The switcher and hreflang offer only the languages this page is written in, each at its own
        // URL (path() writes it from the folder path); the others get their home page.
        $alternates = $fallbacks = [];
        $translations = $this->app->pages->translations($page['path']);
        foreach (array_keys($this->app->site->languages) as $language) {
            if (in_array($language, $translations, true)) {
                $alternates[$language] = $this->path('page', ['path' => $page['path']], $language);
            } else {
                $fallbacks[$language] = $this->path('home', [], $language);
            }
        }
        $this->app->site->setAlternates($alternates, $fallbacks);
        PageSeo::apply($this->app->seo, $page);

        // `template:` in the front matter picks another template, e.g. pages/about.twig.
        return $this->render($page['template'] ?? 'page.twig', ['page' => $page]);
    }
}
