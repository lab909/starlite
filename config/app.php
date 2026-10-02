<?php

// Secrets come from the environment or the gitignored .env file, never from the repo or the frontend bundle.
// Generate a secret with `openssl rand -hex 32`. See .env.example.
return [
    'secret' => getenv('APP_SECRET') ?: throw new RuntimeException('APP_SECRET is not set.'),
    'debug' => getenv('APP_DEBUG') === '1',
];
