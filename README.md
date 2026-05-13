# PublicGraph

> Cartographie d'influence des acteurs publics et privés.
> Open source, open data, factuel, sourcé.

[![License](https://img.shields.io/badge/license-TBD-lightgrey.svg)](#licence)
[![Status](https://img.shields.io/badge/status-en%20conception-orange.svg)](#avancement)
[![Symfony](https://img.shields.io/badge/Symfony-8.0-black.svg?logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4.svg?logo=php)](https://www.php.net)

---

## Le projet

PublicGraph est une plateforme web qui **cartographie les appartenances factuelles** des acteurs d'influence — politiciens, hauts fonctionnaires, dirigeants d'entreprise, patrons de presse, banquiers, lobbyistes — à des **organisations d'influence** (Bilderberg, World Economic Forum, Trilateral Commission, conseils d'administration, partis politiques, etc.).

Le projet trace également les **trajectoires public/privé** dites « portes tournantes » : quand un acteur passe d'un mandat politique à un poste privé chez une entreprise potentiellement bénéficiaire de ses décisions antérieures.

L'objectif est de fournir un **outil de transparence factuel et sourcé**, et non un site d'opinion ou de dénonciation.

### Ce que le projet n'est pas

- ❌ Un site d'opinion ou de dénonciation
- ❌ Un blog ou un media éditorial
- ❌ Un agrégateur de théories du complot
- ❌ Un outil de notation ou de classement moral des personnes

### Ce que le projet est

- ✅ Une base de données structurée d'appartenances vérifiables et sourcées
- ✅ Un outil de visualisation des réseaux d'influence
- ✅ Une plateforme participative à modération stricte
- ✅ Un projet open source et open data

---

## Documentation projet (spec-driven)

La méthodologie **spec-driven** (constitution, specify, plan, tasks, design) et le pilote **CLAUDE.md** ne sont **pas versionnés** dans ce dépôt public : conservez une copie locale ou interne (`specs/`, `CLAUDE.md`) pour le développement et les revues de conformité.

Toute évolution majeure doit continuer à s’appuyer sur ces documents **avant** implémentation, même s’ils ne figurent pas sur GitHub.

---

## Stack technique

- **Backend** : PHP 8.5 + Symfony 8.0
- **Base de données** : MariaDB 10.11
- **Cache et queue** : Redis 7
- **Recherche** : Meilisearch 1.x
- **Frontend** : Twig + Hotwire (Turbo + Stimulus) + Symfony UX (Live Components, Autocomplete)
- **Back-office** : EasyAdmin **5.x** (`easycorp/easyadmin-bundle` ^5), préfixe `/admin` — ne pas utiliser EasyAdmin 4.x (contraintes documentées dans le plan technique interne).
- **CSS** : TailwindCSS avec design tokens custom (référence design en copie locale `specs/design.md` hors dépôt).
- **Graphes interactifs** : Cytoscape.js
- **Reverse proxy** : Nginx (HTTP vers PHP-FPM ; TLS en amont, ex. Coolify)
- **Hébergement cible** : VPS OVHcloud
- **CI / déploiement** : contrôle qualité en local (`make lint`, `make test`) ; déploiement **Coolify**. Le répertoire `.github/` n’est pas versionné (restaurer des workflows en local si besoin).

Les choix d’architecture détaillés (bundles, perf, déploiement) sont dans le **plan** spec-driven, en copie locale hors dépôt.

---

## CI, production et scripts

- **CI** : non versionnée dans ce dépôt ; en local : `make lint` (PHP-CS-Fixer + PHPStan) et `make test` (PHPUnit, MariaDB / Redis / Meilisearch via Docker). Modèle possible : workflow sur `ubuntu-latest`, PHP 8.5, `composer install`, puis les mêmes commandes.
- **Déploiement** : **Coolify** (variables d’environnement, migrations post-déploiement, worker Messenger). Checklist d’exploitation, monitoring et pré-lancement : **hors dépôt** (conserver en wiki ou doc interne).
- **Image Docker & compose** : `Dockerfile`, `docker-compose.yml`, `compose.override.yaml`, `docker/nginx.prod.conf`, modèle d’environnement **`.env.example`** (section prod en commentaires pour `.env.prod`).
- **Bootstrap VPS & sauvegardes** : scripts shell d’exploitation (`vps-init`, `deploy-init`, `backup-db`) — **hors dépôt** ; conserver une copie interne ou sur le serveur.
- **Contribution externe** : [CONTRIBUTING.md](CONTRIBUTING.md) et page publique [publicgraph.org/contribute](https://publicgraph.org/contribute) (liens vers le dépôt GitHub et le parcours contributeur).

---

## Quick start (développement local)

### Prérequis

- Docker + Docker Compose
- Git
- Make

### Démarrage

Le modèle versionné est **`.env.example`** (placeholders `CHANGEME`, variables Docker Compose, section production en commentaires). Les fichiers réels **`.env`**, **`.env.local`**, **`.env.prod`**, **`.env.dev`** et les copies locales **`.env.dist`** / **`.env.*.dist`** ne sont pas versionnés (voir `.gitignore`). Après un clone : `cp .env.example .env`, éditer les secrets, puis `make up`. En production : `docker compose -f docker-compose.yml --env-file .env.prod …` en construisant `.env.prod` à partir de la section commentée du même fichier d’exemple.

```bash
# Clone
git clone https://github.com/jasonniv/publicgraph.git
cd publicgraph

# Environnement local (obligatoire avant Docker / Symfony)
cp .env.example .env
# Éditer .env : remplacer les CHANGEME (APP_SECRET, mot de passe DB, clé Meilisearch, etc.)

# Démarrage des conteneurs
make up

# Installation des dépendances et schéma DB
make install

# Chargement des fixtures de développement
make fixtures-dev

# L'application est accessible sur http://localhost
```

### Commandes Make principales

```bash
make up              # Démarre tous les conteneurs
make down            # Arrête tous les conteneurs
make shell           # Ouvre un shell dans le conteneur publicgraph-php
make logs            # Affiche les logs
make migration       # Crée une nouvelle migration
make migrate         # Applique les migrations
make fixtures-dev    # Charge le dataset de développement
make fixtures-test   # Charge le dataset minimal pour les tests
make test            # Lance la suite de tests PHPUnit
make lint            # Lance PHP-CS-Fixer + PHPStan
make styleguide      # Ouvre la page /styleguide en dev (référence visuelle)
```

### Comptes de test (après `make fixtures-dev`)

| Email | Rôle | Mot de passe |
|---|---|---|
| `admin@example.com` | `ROLE_ADMIN` | `TestPassword123!` |
| `mod@example.com` | `ROLE_MODERATOR` | `TestPassword123!` |
| `user@example.com` | `ROLE_USER` | `TestPassword123!` |

---

## Avancement

### Lot M1 — Fondations techniques

- [x] T1.1 Initialisation du dépôt
- [x] T1.2 Skeleton Symfony 8
- [x] T1.3 Docker Compose dev
- [x] T1.4 Configuration des bundles socles
- [x] T1.5 Configuration frontend (Hotwire + AssetMapper)
- [x] T1.6 Layout de base + design tokens
- [x] T1.7 Configuration robots.txt anti-IA
- [x] T1.8 CI GitHub Actions
- [x] T1.9 Structure modulaire src/Module/
- [x] T1.10 Tests d'environnement

### Lot M2 — Modèle de données + admin minimal

- [x] T2.1 Entités Country + référentiel
- [x] T2.2 Entité User + Security
- [x] T2.3 Entité Person + PersonTranslation
- [x] T2.4 Entité Organization + OrganizationTranslation + Party
- [x] T2.5 Entité Membership + Position
- [x] T2.6 Entité LegislativeAction + RevolvingDoor
- [x] T2.7 Entité Source + EntitySource polymorphique
- [x] T2.8 Entités ChangeProposal + Revision + Report + RightOfReplyRequest
- [x] T2.9 Entité PersonSimilarity
- [x] T2.10 Fixtures de test (Foundry)
- [x] T2.11 EasyAdmin minimal pour modération
- [x] T2.12 Tests fonctionnels modèle

### Lot M3 — Consultation publique

- [x] T3.1 Page liste des Person
- [x] T3.2 Filtres dynamiques sur la liste
- [x] T3.3 Page fiche Person
- [x] T3.4 Page fiche Organization
- [x] T3.5 Page liste Organization
- [x] T3.6 Recherche full-text Meilisearch
- [x] T3.7 Mini-graphe sur fiche Person
- [x] T3.8 Page mentions légales et autres pages statiques
- [x] T3.9 Page accueil
- [x] T3.10 Tests E2E parcours visiteur

### Lot M4 — Contribution et modération

- [x] T4.1 Workflow Symfony pour ChangeProposal
- [x] T4.2 Workflow Symfony pour les fiches en pending
- [x] T4.3 Vérification email + onboarding
- [x] T4.4 Tableau de bord contributeur
- [x] T4.5 Wizard de création de fiche Person
- [x] T4.6 Formulaire de proposition de modification
- [x] T4.7 Validateur anti-accusation pour RevolvingDoor
- [x] T4.8 Backoffice modération customisé
- [x] T4.9 Formulaires de signalement et droit de réponse
- [x] T4.10 Tests E2E parcours contribution

### Lot M5 — Wikidata + proximité + graphe global

- [x] T5.1 Client SPARQL Wikidata
- [x] T5.2 Mapper Wikidata → entités locales
- [x] T5.3 Commande de synchronisation initiale Wikidata
- [x] T5.4 Synchronisation continue (cron hebdo)
- [x] T5.5 Calculateur de score de proximité
- [x] T5.6 Commande de recalcul + cron
- [x] T5.7 Données réelles dans le mini-graphe Person
- [x] T5.8 Page graphe global
- [x] T5.9 Tests perf et benchmarks

### Lot M6 — Légal, SEO, déploiement

- [x] T6.1 Sitemap dynamique
- [x] T6.2 Métadonnées dynamiques + JSON-LD
- [x] T6.3 Configuration Nginx + sécurité headers
- [x] T6.4 Init VPS + scripts de bootstrap
- [x] T6.5 Pipeline déploiement GitHub Actions
- [x] T6.6 Monitoring Sentry + Uptime Kuma
- [x] T6.7 Vérification automatique des sources
- [x] T6.8 Page de statut public et page d'erreur
- [x] T6.9 Documentation README + CONTRIBUTING + GitHub Issues templates
- [ ] T6.10 Préparation lancement public (checklist opérationnelle hors dépôt)

---

## Principes éditoriaux

PublicGraph applique des principes stricts pour garantir la rigueur du contenu publié. Le détail (sourçage, modération, interdits éditoriaux) figure dans la **constitution** spec-driven, en copie locale hors dépôt.

### Acceptés sur la plateforme

- ✅ Participation à un événement publié officiellement (ex. Bilderberg Meeting 2018)
- ✅ Mandat politique élu ou nommé (registre officiel)
- ✅ Position dans un conseil d'administration (registre du commerce)
- ✅ Adhésion à un parti politique
- ✅ Embauche dans une entreprise après mandat (annonce officielle, presse établie)
- ✅ Action législative attribuée (vote, loi portée, décret signé)

### Refusés sur la plateforme

- ❌ Qualificatifs d'opinion (« globaliste », « mondialiste », « atlantiste »)
- ❌ Allégations non confirmées (« corrompu », « à la solde de »)
- ❌ Liens implicites de causalité (les portes tournantes sont présentées comme des chronologies de faits, jamais comme des accusations)

### Sourçage obligatoire

Toute appartenance, tout mandat, toute porte tournante doit être accompagné d'**au moins une source URL vérifiable**. Les sources sont stockées en base et affichées publiquement.

---

## Comment contribuer

PublicGraph accueille trois types de contributions.

### 1. Contribuer du contenu (fiches, appartenances, sources)

Inscris-toi sur la plateforme : [publicgraph.org/register](https://publicgraph.org/register) (route applicative `/register` ; domaine et checklist pré-lancement selon ta doc d’exploitation), puis :

- Propose une nouvelle fiche d'acteur via le wizard de création
- Propose une modification d'une fiche existante
- Ajoute des sources documentaires à des appartenances déjà publiées

Toutes les contributions passent par la modération avant publication. Le sourçage est obligatoire.

### 2. Contribuer du code

Les contributions de code sont les bienvenues. Lis [CONTRIBUTING.md](CONTRIBUTING.md) pour le détail du processus, puis :

- Consulte les [issues ouvertes](https://github.com/jasonniv/publicgraph/issues)
- Propose une issue pour discuter une feature avant de coder
- Forke, crée une branche `feat/Mx-Ty-description`, code, et soumets une PR

Le code doit respecter PSR-12, passer PHPStan niveau 8, et être couvert par des tests.

### 3. Contribuer financièrement

Le projet est gratuit et le restera. Si tu veux soutenir son développement et son hébergement :

- **Liberapay** : compte non publié dans ce dépôt pour l'instant ; l'URL sera ajoutée ici dès ouverture du compte (modèle économique décrit dans la constitution, copie locale hors dépôt).
- **Tipeee** : idem.

À terme, lorsqu'une association loi 1901 sera créée, des dons défiscalisables seront possibles.

---

## Signaler un contenu

### Erreur factuelle ou contenu litigieux

Utilise le formulaire de signalement public : [publicgraph.org/report](https://publicgraph.org/report) (route `/report`). Aucune inscription n'est nécessaire.

### Vous êtes la personne fichée et vous souhaitez exercer votre droit de réponse

Utilise le formulaire de droit de réponse accessible depuis chaque fiche personne (`/right-of-reply/{slug}`), ou la page d'information [publicgraph.org/right-of-reply](https://publicgraph.org/right-of-reply) (route `/right-of-reply`).

---

## Licence

### Code

Licence à arbitrer entre AGPL-3.0 et MIT. Voir issue [#TBD]. En attendant, le code est consultable mais pas redistribuable hors du cadre de contribution au projet lui-même.

### Données

Licence à arbitrer entre CC-BY-SA 4.0 et CC0. Voir issue [#TBD].


---

## État du projet et contact


**Hébergement** : OVHcloud SAS (France).

**Contact éditorial et technique** : [contact@publicgraph.org](mailto:contact@publicgraph.org) — activer la boîte et les enregistrements DNS / MX avant le lancement public (voir checklist).

**GitHub Issues** : pour les bugs, suggestions de feature, et corrections factuelles spécifiques au code (les corrections factuelles concernant les fiches publiques se font via le formulaire de signalement de la plateforme).

---

## Inspirations

Le projet s'inscrit dans la lignée des initiatives de transparence civique et de journalisme de données :

- [Mediapart](https://mediapart.fr) pour la rigueur éditoriale
- [Investigate Europe](https://www.investigate-europe.eu) pour le sourçage rigoureux
- [OCCRP Aleph](https://aleph.occrp.org) pour la cartographie d'influence
- [Wikidata](https://www.wikidata.org) pour le bootstrap des données factuelles
- [data.gouv.fr](https://www.data.gouv.fr) pour l'esthétique civique
- [Anticor](https://www.anticor.org), [Transparency International](https://transparency-france.org), [Sherpa](https://www.asso-sherpa.org) pour la mission de transparence
