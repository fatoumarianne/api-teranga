#!/bin/sh
# ---------------------------------------------------------------------------
# Demarrage de l'API en production.
#
#   1. applique les migrations (creation ou mise a jour des tables)
#   2. insere le jeu de donnees de demonstration si la base est vide
#   3. demarre le serveur sur le port fourni par l'hebergeur
#
# Le seeder verifie lui-meme si des donnees existent deja et ne fait rien
# dans ce cas : on peut donc l'appeler a chaque demarrage sans risque.
# ---------------------------------------------------------------------------
set -e

echo "==> Migrations de la base de donnees"
php artisan migrate --force

echo "==> Jeu de donnees de demonstration"
php artisan db:seed --force

echo "==> Mot de passe administrateur"
php artisan teranga:mot-de-passe-admin

echo "==> Demarrage du serveur sur le port ${PORT:-8000}"
exec php artisan serve --host 0.0.0.0 --port "${PORT:-8000}"
