# ─── Build stage ─────────────────────────────────────────────────────────────
# On utilise l'image PHP comme base du build pour que le plugin Vite
# wayfinder puisse appeler `php artisan wayfinder:generate` pendant le build.
FROM serversideup/php:8.5-frankenphp AS builder

USER root

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg libicu-dev inotify-tools \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g pnpm \
    && docker-php-ext-install intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Dépendances PHP d'abord (layer mis en cache tant que composer.lock ne change pas)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-progress

# Dépendances Node (layer mis en cache tant que pnpm-lock.yaml ne change pas)
COPY package.json pnpm-lock.yaml .npmrc pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

# Code source
COPY . .

# Autoload optimisé + build Vite (wayfinder peut appeler artisan depuis ici)
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && pnpm run build \
    && rm -rf node_modules


# ─── Queue worker ─────────────────────────────────────────────────────────────
FROM serversideup/php:8.5-cli AS worker

USER root
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev \
    && docker-php-ext-install intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --chown=www-data:www-data --from=builder /var/www/html /var/www/html


# ─── Serveur web ──────────────────────────────────────────────────────────────
FROM serversideup/php:8.5-frankenphp

USER root
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev \
    && docker-php-ext-install intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --chown=www-data:www-data --from=builder /var/www/html /var/www/html
