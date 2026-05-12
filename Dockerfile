FROM php:8.5-fpm-alpine

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS icu-dev libzip-dev \
    && apk add --no-cache git unzip icu-libs libzip \
    && docker-php-ext-install -j"$(nproc)" \
       pdo_mysql intl zip opcache exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
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
