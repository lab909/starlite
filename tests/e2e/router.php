<?php

declare(strict_types=1);

// PHP's built-in server for the end-to-end tests (playwright.config.js), like nginx's try_files:
// files in public/ are served as they are, everything else goes to the app.
$file = dirname(__DIR__, 2) . '/public' . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_file($file)) {
    return false;
}
require dirname(__DIR__, 2) . '/public/index.php';
