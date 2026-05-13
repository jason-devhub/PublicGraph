#!/bin/sh
set -e

cd /app

install -d -o www-data -g www-data var/cache var/log

# Supprime le cache compilé de l'env courant pour éviter des routes obsolètes après un redéploiement.
# Pas de `cache:warmup` ici : il échoue en prod (paramètre %kernel.share_dir% absent au boot console).
# Le cache est reconstruit au premier hit HTTP / première commande Symfony.
cache_env="${APP_ENV:-prod}"
if [ -d "var/cache/${cache_env}" ]; then
    rm -rf "var/cache/${cache_env}"
fi

chown -R www-data:www-data var

if [ "$#" -eq 0 ]; then
    set -- nginx -g "daemon off;"
fi

# Lance PHP-FPM en arrière-plan uniquement si Nginx est le processus principal.
if [ "$1" = "nginx" ] || [ "$1" = "nginx-debug" ]; then
    php-fpm -D
fi

exec "$@"
