FROM php:8.5-fpm-alpine

# Client Redis (ext-redis) : le serveur est un autre conteneur ; ici on compile l’extension PHP.
# Sur Alpine, pecl/redis échoue souvent sans linux-headers / openssl-dev / pcre-dev.
# yes '' | pecl : invites non interactives (CI / Coolify sans TTY).
#
# Paquets *-dev (Alpine/Debian) = en-têtes et libs pour **compiler** du C (extensions PHP), pas « mode dev »
# applicatif. Ils sont retirés à la fin de ce RUN (`apk del .build-deps`). En runtime on garde surtout
# icu-libs et libzip (bibliothèques partagées pour intl/zip déjà compilées).
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        linux-headers \
        openssl-dev \
        pcre-dev \
    && apk add --no-cache git unzip icu-libs libzip \
    && docker-php-ext-install -j"$(nproc)" \
       pdo_mysql intl zip opcache exif \
    && yes '' | pecl install -o -f redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear \
    && apk del .build-deps

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
