# ---------------------------------------------------------------------------
# Image de production de l'API Laravel — Club Sportif Teranga
#
# Render construit cette image a partir du depot GitHub, puis lance le
# conteneur. Le script docker-entrypoint.sh applique les migrations avant de
# demarrer le serveur.
# ---------------------------------------------------------------------------
FROM php:8.3-cli

# Bibliotheques necessaires a l'extension PostgreSQL de PHP
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev unzip git \
 && docker-php-ext-install pdo pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# Composer, le gestionnaire de dependances de PHP
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Dependances de production uniquement, puis dossiers de travail de Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction \
 && mkdir -p storage/framework/cache storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
 && chmod -R 777 storage bootstrap/cache \
 && sed -i 's/\r$//' docker-entrypoint.sh \
 && chmod +x docker-entrypoint.sh

# Render fournit le port a utiliser dans la variable PORT
ENV PORT=8000
EXPOSE 8000

CMD ["./docker-entrypoint.sh"]
