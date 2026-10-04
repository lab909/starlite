---
question: How fast is it?
order: 3
---
On deploy, routes, templates, posts and collections are compiled into plain PHP arrays that
Opcache keeps in memory, so a request reads no files and runs no queries.
