# Contributing to Starlite

Starlite is three repositories:

| Repository | What it is | In a site |
|---|---|---|
| [lab909/starlite](https://github.com/lab909/starlite) | this site skeleton | the starting point, via **Use this template** |
| [lab909/starlite-framework](https://github.com/lab909/starlite-framework) | the framework package, `starlite/framework` | `vendor/starlite/framework`, installed by Composer |
| [lab909/starlite-framework-docs](https://github.com/lab909/starlite-framework-docs) | the documentation (VitePress) | not included |

## Maintainer setup: all three side by side

Changes often span the framework, the skeleton and the docs. Work on them together by cloning the
other two repositories **inside** this one. Both locations are gitignored, so nothing about them
is ever published with the skeleton:

```sh
git clone git@github.com:lab909/starlite.git && cd starlite
git clone git@github.com:lab909/starlite-framework.git packages/starlite
git clone git@github.com:lab909/starlite-framework-docs.git docs
ddev start
ddev composer update starlite/framework     # now symlinks packages/starlite instead of using GitHub
```

How it works: `composer.json` lists a path repository (`packages/*`) before the framework's GitHub
repository. When `packages/starlite` exists, Composer prefers it and symlinks it into
`vendor/starlite/framework`, so framework edits take effect immediately. In a regular clone the
`packages/` folder is empty (only `.gitkeep`) and Composer installs from GitHub.

Because the source differs, `composer.lock` is never committed in the skeleton.

Each clone is its own git repository: commit and push framework changes from `packages/starlite`,
docs changes from `docs/`, and skeleton changes from the root.

## Running the checks

```sh
# the skeleton (app tests)
ddev composer test && ddev composer analyse

# the skeleton in a real browser (Playwright, against the production build)
ddev npm run build && ddev exec CHROMIUM_PATH=/usr/bin/chromium npm run test:e2e

# the framework
cd packages/starlite && composer update && composer test && composer analyse
cd packages/starlite && npm install && npm test    # Vitest: the browser helpers

# the docs
cd docs && npm install && npm run build     # npm run dev → https://<project>.ddev.site:5174
```

Without `npm run build` in the root, the app tests that check built assets are skipped.

## Guidelines

- Keep the framework free of `App\` code: anything a site might change belongs in the skeleton, or
  needs an extension point in the framework.
- Every behaviour change comes with a test in the framework or the app suite, and a docs update.
- Prefer proven libraries for infrastructure; Starlite's own code is the glue between them.
- PHPStan level 8 must stay clean in both repositories.

## Suggesting docs changes

Every docs page has an **Edit this page on GitHub** link. Small fixes can go straight to the docs
repository as a pull request.
