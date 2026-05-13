#!/bin/sh
set -e

cd /app

install -d -o www-data -g www-data var/cache var/log

# Supprimer le cache compilé UNIQUEMENT pour le service web (nginx).
# Le worker ne le supprime pas : il partage le volume var/ avec le web et pourrait
# créer une race condition ou des fichiers root si on efface ici.
if [ "$1" = "nginx" ] || [ "$1" = "nginx-debug" ] || [ "$#" -eq 0 ]; then
    cache_env="${APP_ENV:-prod}"
    if [ -d "var/cache/${cache_env}" ]; then
        rm -rf "var/cache/${cache_env}"
    fi
fi

chown -R www-data:www-data var

if [ "$#" -eq 0 ]; then
    set -- nginx -g "daemon off;"
fi

if [ "$1" = "nginx" ] || [ "$1" = "nginx-debug" ]; then
    php-fpm -D
    exec "$@"
fi

# Toute autre commande (messenger:consume, bin/console…) s'exécute en www-data
# pour que les fichiers créés dans var/ soient cohérents avec le service web.
exec gosu www-data "$@"
