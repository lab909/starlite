<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Routes live in config/routes.php so bin/console can compile them too.
Starlite\Kernel::boot(dirname(__DIR__))->run();
