# Contribuer à PublicGraph

Merci de votre intérêt. Ce dépôt suit une gouvernance **spec-driven** : lisez au minimum la **constitution** et ce fichier avant une contribution substantielle (copies locales des specs et de `CLAUDE.md` — non versionnés sur ce dépôt public).

## Code de conduite

Nous adoptons le [Contributor Covenant](https://www.contributor-covenant.org/version/2/1/code_of_conduct/) (v2.1) comme référence de comportement dans les issues, PR et espaces de discussion.

## Signaler un bug ou proposer une évolution

- Ouvrez une **issue** sur GitHub avec un titre et une description clairs (bug, fonctionnalité, correction de données).
- Pour une correction factuelle sur une fiche publiée, privilégiez le [formulaire de signalement](https://publicgraph.org/report) sur le site public.

## Pull requests

- Une PR = un sujet clair (idéalement lié à une tâche `Mx.Ty` de votre copie locale de `tasks.md`, hors dépôt).
- Branche : `feat/Mx-Ty-description-courte` (ou `fix/…`, `chore/…`).
- Mettre à jour les tests et la documentation impactés **dans le périmètre versionné** (README, `CONTRIBUTING.md`, etc.).
- Message de commit conventionnel : `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`, `test:`.

## Qualité du code

- **PSR-12** via PHP CS Fixer (`make lint` ou équivalent dans le conteneur `app`).
- **PHPStan** niveau 8 (`make lint` ou `make phpstan` selon le Makefile du projet).
- **PHPUnit** : `make test` avant merge.
- Pas de désactivation de tests pour « faire passer » la CI.

## Revue

Les PR sont relues au regard des règles éditoriales et techniques (constitution, specify, plan, design en **copie locale**). Les refus motivés par non-conformité sont possibles.
