<?php

// Data collections: structured content beyond blog posts (FAQs, team, products, a catalogue…).
// Each one lives in content/<name>/ as <slug>.md (fields in the front matter, optional Markdown body)
// or <slug>.yaml (fields only); translations are <slug>.<language>.md and keep the fields they omit.
// In Twig: {% for item in collections.faq %}…{% endfor %}, collections.faq.find('slug');
// in PHP: $this->app->collections['faq']. Files are checked against the fields below: a typo,
// a wrong type or a missing field stops with the file name.
//
// Field types (a leading ? makes a field optional): string, int, float, bool, date (YYYY-MM-DD),
// url (a /path or https:// URL), markdown (rendered to HTML), list (plain values), array (any YAML).
//
// Options:
//   sort      a field, '-field' for descending; default: by slug
//   fallback  true: items without a translation appear in the default language (default: hidden)
//   json      true or a list of fields: served at /data/<name>.json for JavaScript (default: none).
//             An allowlist: only those fields are public.
return [
    'faq' => [
        'fields' => [
            'question' => 'string',
            'order' => 'int',
        ],
        'sort' => 'order',
    ],
];
