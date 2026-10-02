<?php

declare(strict_types=1);

namespace App\Controller;

use Spatie\SchemaOrg\Schema;
use Starlite\Controller;

final class HomeController extends Controller
{
    public function __invoke(): string
    {
        $seo = $this->app->seo;
        $seo->schema(Schema::webSite()->name($seo->site['name'])->url($seo->url('/'))->description($seo->site['description']));

        return $this->render('index.twig');
    }
}
