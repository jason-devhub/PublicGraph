# Image PHP-FPM **production** (publicgraph-php, publicgraph-worker sur Coolify).
#
# En local, `docker compose build` fusionne `compose.override.yaml` : le build utilise alors
# `docker/Dockerfile.dev` (Debian), pas ce fichier. Coolify n’utilise que `docker-compose.yml` →
# c’est **ce** Dockerfile qui est construit en prod ; ne pas le confondre avec le build dev.
#
# Base Debian bookworm (comme Dockerfile.dev) : même chaîne d’extensions, builds CI/Coolify plus
# fiables que l’ancienne variante Alpine + apk + PECL (échecs « exit code 2 » sur certains builders).
# `php:8.5-fpm-bookworm` charge déjà Zend OPcache. Ne pas le recompiler ici : sur PHP 8.5,
# certains builders échouent avec `cp: cannot stat 'modules/*'` pendant l'installation.

FROM php:8.5-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    libicu-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j1 pdo_mysql intl zip exif

RUN yes '' | pecl install -o -f redis-6.3.0 \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1
# Valeurs uniquement destinées aux commandes Symfony exécutées au build (`asset-map:compile`).
# En runtime, `docker-compose.yml` les remplace par les variables Coolify réelles.
ENV APP_SECRET=build_time_placeholder_change_in_runtime
ENV APP_SHARE_DIR=var/share
ENV DATABASE_URL="mysql://publicgraph:publicgraph@127.0.0.1:3306/publicgraph?serverVersion=10.11.0-MariaDB&charset=utf8mb4"
ENV REDIS_URL=redis://127.0.0.1:6379
ENV MEILISEARCH_URL=http://publicgraph-search:7700
ENV MEILISEARCH_KEY=
ENV MAILER_DSN=null://null
ENV SENTRY_DSN=
ENV DEFAULT_URI=http://localhost
ENV MESSENGER_ASYNC_DSN=redis://127.0.0.1:6379/messages
ENV MESSENGER_SCHEDULER_DSN=redis://127.0.0.1:6379/scheduler
ENV APP_DEFAULT_LOCALE=en
ENV APP_ENABLED_LOCALES=en,fr
ENV STATUS_SHOW_TELEMETRY=0

COPY . .

# SymfonyRuntime appelle Dotenv sur `/app/.env` ; le vrai `.env` est exclu du contexte (`.dockerignore`).
# `.env.example` est versionné : copie minimale pour le boot console au build. Les `ENV` ci-dessus
# et les variables Coolify au runtime priment sur les placeholders du fichier.
RUN cp .env.example .env

RUN mkdir -p var/cache var/log \
    && composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

RUN php bin/console tailwind:build --env=prod --no-debug -vvv

RUN php bin/console importmap:install --env=prod --no-debug -vvv

RUN php bin/console asset-map:compile --env=prod --no-debug -vvv

RUN chown -R www-data:www-data var

USER www-data

CMD ["php-fpm"]
