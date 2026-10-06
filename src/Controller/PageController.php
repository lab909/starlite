<?php

declare(strict_types=1);

namespace App\Controller;

use Starlite\Controller;
use Starlite\Forms\Submission;
use Starlite\Pages\PageSeo;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content pages from content/pages/<path>/index.md (Privacy, About…). The route is a catch-all with
 * a low priority (config/routes.php), so every other route wins and unknown URLs end here as a 404.
 *
 * A page with `form: contact` in its front matter shows that form (config/forms.php) and receives it:
 * with Datastar the form is re-rendered in place (_forms/<form>.twig); without JavaScript the page is
 * shown again with the errors, or reloaded with ?sent=1 so a refresh can't send it twice.
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
        if (!$page['cdn']) {
            $this->app->cdn->skip(); // `cdn: false` in the front matter: never cached at the CDN
        }

        $request = $this->app->request();
        $form = null;
        if ($page['form'] !== null) {
            $form = $request->isMethod('POST')
                ? $this->app->forms->submit($page['form'], $request)
                : new Submission($page['form'], sent: $request->query->has('sent'));
            if ($request->isMethod('POST') && $request->headers->has('Datastar-Request')) {
                return $this->app->stream("_forms/{$page['form']}.twig", ['form' => $form, 'page' => $page]);
            }
            if ($request->isMethod('POST') && $form->sent) {
                return new RedirectResponse($this->path('page', ['path' => $page['path']]) . '?sent=1', Response::HTTP_SEE_OTHER);
            }
        } elseif ($request->isMethod('POST')) {
            return $this->app->error(405, $this->t('Method not allowed.'), ['Allow' => 'GET']);
        }

        // `template:` in the front matter picks another template, e.g. pages/about.twig.
        $html = $this->render($page['template'] ?? 'page.twig', ['page' => $page, 'form' => $form]);

        return $form !== null && $form->errors !== [] ? new Response($html, Response::HTTP_UNPROCESSABLE_ENTITY) : $html;
    }
}
