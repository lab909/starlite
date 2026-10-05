<?php

// Forms: the fields a visitor fills in, how they're checked, and where they're emailed (with Symfony
// Mailer: MAILER_DSN and MAILER_FROM in .env). Submissions are emailed, never stored.
//
// Field types: text, email, textarea, choice (with 'choices' => [...]), checkbox. Options: required,
// min and max (characters). Messages for wrong values come from translations/<code>.php. More rules:
// $app->forms->rule('contact', 'message', fn ($value) => …) in config/bootstrap.php.
//
// Spam checks, all on this server (nothing goes to a third party):
//   honeypot     a hidden field only bots fill in
//   timing       a signed token: forms sent within N seconds of being shown are bots
//                (pages with a form are therefore never cached)
//   max_links    more than N links is refused, with a message
//   rate_limit   at most N messages per visitor ("5/hour"), counted with a keyed hash of the IP
// A rejected bot sees "sent" like everyone else; the log says why, never what was sent.
return [
    'contact' => [
        'fields' => [
            'name' => ['type' => 'text', 'required' => true, 'max' => 100],
            'email' => ['type' => 'email', 'required' => true],
            'message' => ['type' => 'textarea', 'required' => true, 'min' => 10, 'max' => 5000],
        ],
        'to' => getenv('CONTACT_TO') ?: null,
        'subject' => 'Message from {name}',
        'spam' => ['honeypot', 'timing' => 3, 'max_links' => 2, 'rate_limit' => '5/hour'],
    ],
];
