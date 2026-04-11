#!/bin/bash
set -e

cd /var/www/html

echo "[dev] Installing Node dependencies..."
CI=true pnpm install --frozen-lockfile

echo "[dev] Generating Wayfinder routes..."
php artisan wayfinder:generate

echo "[dev] Starting Wayfinder watcher..."
(while true; do
    inotifywait -r -q -e modify,create,delete,move \
        routes/ app/Http/Controllers/ 2>/dev/null || true
    echo "[wayfinder] Regenerating routes..."
    php artisan wayfinder:generate
done) &

echo "[dev] Starting Vite dev server..."
pnpm run dev
