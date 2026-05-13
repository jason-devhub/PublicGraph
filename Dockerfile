# Image PHP-FPM **production** (publicgraph-php, publicgraph-worker sur Coolify).
#
# En local, `docker compose build` fusionne `compose.override.yaml` : le build utilise alors
# `docker/Dockerfile.dev` (Debian), pas ce fichier. Coolify n’utilise que `docker-compose.yml` →
# c’est **ce** Dockerfile qui est construit en prod ; ne pas le confondre avec le build dev.
#
# Base Debian bookworm (comme Dockerfile.dev) : même chaîne d’extensions, builds CI/Coolify plus
# fiables que l’ancienne variante Alpine + apk + PECL (échecs « exit code 2 » sur certains builders).
# docker-php-ext-install reste volontairement en -j1 : sur PHP 8.5, certains builders déclenchent
# une race `cp: cannot stat 'modules/*'` avec les builds parallèles.

FROM php:8.5-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    libicu-dev libzip-dev \
    && docker-php-ext-install -j1 opcache pdo_mysql intl zip exif \
    && yes '' | pecl install -o -f redis-6.3.0 \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY . .

RUN mkdir -p var/cache var/log \
    && composer install --no-dev --no-interaction --no-scripts --optimize-autoloader \
    && php bin/console asset-map:compile --env=prod --no-debug \
    && chown -R www-data:www-data var

USER www-data

CMD ["php-fpm"]
