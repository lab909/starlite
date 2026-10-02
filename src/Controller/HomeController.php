<?php

declare(strict_types=1);

namespace App\Controller;

use Starlite\Controller;

final class HomeController extends Controller
{
    public function __invoke(): string
    {
        return $this->render('index.twig');
    }
}
