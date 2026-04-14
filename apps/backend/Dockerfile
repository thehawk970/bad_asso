# ─── Build stage ─────────────────────────────────────────────────────────────
# On utilise l'image PHP comme base du build pour que le plugin Vite
# wayfinder puisse appeler `php artisan wayfinder:generate` pendant le build.
FROM registry.thehawk970.space/montrees-club/montrees-php:latest AS builder

USER root
WORKDIR /var/www/html

# Node.js LTS — installé tôt pour profiter du cache Docker
RUN apt-get update -qq \
    && apt-get install -y --no-install-recommends curl ca-certificates \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && corepack enable \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Dépendances PHP (layer mis en cache tant que composer.lock ne change pas)
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

# Autoload optimisé + build Vite (wayfinder appelle artisan depuis ici)
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && pnpm run build \
    && rm -rf node_modules


# ─── Queue worker ─────────────────────────────────────────────────────────────
FROM registry.thehawk970.space/montrees-club/montrees-php:latest AS worker
USER root
COPY --chown=www-data:www-data --from=builder /var/www/html /var/www/html


# ─── Serveur web ──────────────────────────────────────────────────────────────
FROM registry.thehawk970.space/montrees-club/montrees-php:latest

USER root
COPY --chown=www-data:www-data --from=builder /var/www/html /var/www/html
