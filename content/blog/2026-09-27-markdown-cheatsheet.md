---
title: Writing posts in Markdown
date: 2026-09-27
tags: [markdown, guide]
---

Every `.md` file in `content/blog` becomes a post. The file name (minus an optional date prefix)
is the URL slug.

## Front matter

```yaml
title: Required
date: 2026-09-27      # required, YYYY-MM-DD
summary: Optional     # defaults to the first paragraph
tags: [one, two]
slug: custom-url      # optional
draft: true           # only visible with APP_DEBUG=1
```

## GitHub-flavoured extras

| Feature        | Supported |
|----------------|-----------|
| Tables         | yes       |
| ~~Strikethrough~~ | yes    |
| Task lists     | yes       |

- [x] Write the post
- [ ] Run `bin/console deploy`

Raw HTML such as <script>alert(1)</script> is escaped, and external links like
[Datastar](https://data-star.dev) get `rel="noopener noreferrer"`.
