---
title: Writing posts in Markdown
date: 2026-09-27
tags: [markdown, guide]
---

Every post is a folder: `content/blog/YYYY/MM/<slug>/index.md`, next to its images. The folder
name is the URL slug. Drafts go in `content/blog/drafts/<slug>/`.

## Front matter

```yaml
title: Required
date: 2026-09-27      # required, YYYY-MM-DD, must match the YYYY/MM folder
updated: 2026-09-30   # optional
image: cover.png      # optional share image, a file in the post folder
summary: Optional     # defaults to the first paragraph
tags: [one, two]
```

## Images

Put images next to `index.md` and link them relatively: `![Alt text](cover.png)`.

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

## Components

A line of its own like `::name{key="value"}` places a component: a small template from
`templates/_components/`, such as related posts or (soon) a video. Arguments are plain text, numbers
or `true`/`false`:

```md
::related-posts{limit=2}
```

Inside code, like above, it's just text. An unknown component stops the build with the file name.
