#!/usr/bin/env bash
set -e

echo "🚀 Memulai update aplikasi Ujian Online (ujian.nazwagraha.com)..."

git pull origin main

if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction
fi

php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Update Ujian Online berhasil diterapkan!"
