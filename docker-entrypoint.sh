#!/bin/sh
# ---------------------------------------------------------------------------
# Demarrage de l'API en production.
#
#   1. applique les migrations (creation ou mise a jour des tables)
#   2. insere le jeu de donnees de demonstration si SEED_ON_BOOT vaut true
#   3. demarre le serveur sur le port fourni par l'hebergeur
# ---------------------------------------------------------------------------
set -e

echo "==> Migrations de la base de donnees"
php artisan migrate --force

if [ "$SEED_ON_BOOT" = "true" ]; then
  echo "==> Insertion du jeu de donnees de demonstration"
  php artisan db:seed --force
fi

echo "==> Demarrage du serveur sur le port ${PORT:-8000}"
exec php artisan serve --host 0.0.0.0 --port "${PORT:-8000}"
