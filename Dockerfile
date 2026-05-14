# Image unique production : Nginx (port 80) + PHP-FPM (9000 interne) dans le même conteneur.
# Architecture identique à example/docker/deploy/Dockerfile pour la compatibilité Coolify.
# Le service worker (docker-compose.yml) réutilise cette image avec une commande overridée.
#
# Trois stages :
#   php-base    — extensions PHP + ini
#   app-build   — Composer + assets Symfony (tailwind, importmap, asset-map)
#   app         — image finale avec Nginx embarqué (config COPIÉE, jamais montée en volume)

FROM php:8.5-fpm-bookworm AS php-base

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    libicu-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j1 pdo_mysql intl zip exif

RUN yes '' | pecl install -o -f redis-6.3.0 \
    && docker-php-ext-enable redis

COPY docker/deploy/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/deploy/php/memory.ini  /usr/local/etc/php/conf.d/zz-memory.ini

# --- Build applicatif (Composer + assets)
FROM php-base AS app-build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1
# Valeurs build-time uniquement — remplacées par les variables Coolify au runtime.
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

# .env.example est versionné ; les ENV ci-dessus et les variables Coolify priment au runtime.
RUN cp .env.example .env

RUN mkdir -p var/cache var/log \
    && composer install --no-dev --no-interaction --no-scripts --optimize-autoloader

RUN php bin/console tailwind:build --env=prod --no-debug -vvv
RUN php bin/console importmap:install --env=prod --no-debug -vvv
RUN php bin/console asset-map:compile --env=prod --no-debug -vvv

RUN chown -R www-data:www-data var

# --- Image finale : Nginx + PHP-FPM (config nginx embarquée — jamais en volume)
FROM php-base AS app

RUN apt-get update && apt-get install -y --no-install-recommends nginx curl gosu \
    && rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf \
    && rm -rf /var/lib/apt/lists/*

# Par défaut PHP-FPM vide l’environnement des workers (clear_env=yes) : les variables Docker
# (APP_SECRET, DATABASE_URL, …) n’atteignent pas Symfony. Comme sur beaucoup d’images Symfony prod.
RUN sed -i 's/^;clear_env = no$/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

# Valeurs par défaut dans l’image (complètent le compose ; visibles des workers avec clear_env=no).
ENV APP_ENV=prod
ENV APP_DEBUG=0

WORKDIR /app

COPY --from=app-build /app /app
COPY docker/deploy/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/deploy/docker-entrypoint.sh /usr/local/bin/docker-entrypoint-app
RUN chmod +x /usr/local/bin/docker-entrypoint-app

ENTRYPOINT ["docker-entrypoint-app"]
CMD ["nginx", "-g", "daemon off;"]

# Métadonnée HEALTHCHECK : utilisée par l’image (publicgraph-web) et visible aux outils qui
# inspectent le Dockerfile (ex. Coolify). Le service worker surcharge ce healthcheck dans
# docker-compose.yml car il n’expose pas Nginx sur le port 80.
HEALTHCHECK --interval=30s --timeout=5s --start-period=120s --retries=5 \
  CMD curl -fsS http://127.0.0.1/health || exit 1
