<?php

declare(strict_types=1);

namespace App\Controller;

use Starlite\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Custom Datastar action: do any PHP work here, then stream a template back. */
final class ClockController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return $this->stream('_partials/clock.twig', ['now' => date('H:i:s')]);
    }
}
