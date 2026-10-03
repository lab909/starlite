<?php

// English UI texts. Keys are the texts used in templates and controllers, and a missing entry
// shows the key itself, so this file only needs entries whose wording differs from the key, or
// plural forms (ICU MessageFormat: https://unicode-org.github.io/icu/userguide/format_parse/messages/).
return [
    '{minutes} min read' => '{minutes, plural, one {# min read} other {# min read}}',
    '{shown} of {total} posts' => '{shown} of {total, plural, one {# post} other {# posts}}',
];
