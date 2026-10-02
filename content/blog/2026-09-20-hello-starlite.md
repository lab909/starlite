---
title: Hello, Starlite
date: 2026-09-20
summary: A database-free micro framework that serves dynamic pages at static-site speed.
tags: [starlite, php]
---

Starlite is a tiny PHP framework built for sites that are *mostly static* but still need a
little server-side interactivity.

## What's inside

- **Symfony Routing**, compiled to a plain PHP array
- **Twig** templates, compiled to PHP classes
- **Datastar** for reactive UI over server-sent events
- **Markdown** posts like this one, compiled once at deploy time

Everything ends up as PHP files in `var/cache`, so Opcache serves them straight from shared memory.
