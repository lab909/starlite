<?php

declare(strict_types=1);

use Starlite\Kernel;

/*
 * The app's extension point, run once per request (and per console run) before config/routes.php.
 * Register here what the app adds on top of Starlite, so lib/ never has to be edited.
 */
return static function (Kernel $app): void {
    // Services: a closure is a lazy, shared factory; use them with $this->get(...) in controllers.
    // $app->container->set(Mailer::class, fn (Kernel $app) => new Mailer(getenv('MAILER_DSN')));

    // Twig: extensions, globals and functions.
    // $app->twig->addExtension(new App\Twig\AppExtension());
    // $app->twig->addGlobal('support_email', 'help@example.com');

    // Content Security Policy: hosts a feature needs (also in config/app.php `csp.sources`), and inline
    // scripts sent with Datastar's execute_script(), by their exact code.
    // $app->csp->allow('script-src', 'https://plausible.io')->allow('connect-src', 'https://plausible.io');
    // $app->csp->allowScript("console.log('Clock updated')");

    // Deploy: extra build steps, by default just before Opcache is refreshed
    // (`bin/console deploy --list-steps` shows the order).
    // $app->addDeployStep('audio', 'app:build-audio', 'Encode the sound files', before: 'opcache');
    // $app->addDeployStep('sitemap-ping', fn (Kernel $app, $io) => $io->writeln('…'), after: 'opcache');

    // Console commands need no registration: put them in src/Command/ (extend Starlite\Console\AppCommand).
};
