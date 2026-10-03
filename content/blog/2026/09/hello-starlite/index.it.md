---
title: Ciao, Starlite
summary: Un micro framework senza database che serve pagine dinamiche alla velocità di un sito statico.
---

Starlite è un piccolo framework PHP pensato per siti *perlopiù statici* che hanno comunque
bisogno di un po' di interattività lato server.

## Cosa contiene

- **Symfony Routing**, compilato in un semplice array PHP
- template **Twig**, compilati in classi PHP
- **Datastar** per un'interfaccia reattiva tramite server-sent events
- articoli in **Markdown** come questo, compilati una sola volta al deploy

Tutto finisce in file PHP dentro `var/cache`, così Opcache li serve direttamente dalla memoria condivisa.
