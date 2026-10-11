#!/usr/bin/env bash
set -e

echo "🚀 Memulai update aplikasi Ujian Online (ujian.nazwagraha.com)..."

git pull origin main

# Cari binary PHP 8.3+ di cPanel / CloudLinux jika default php CLI masih versi lama
PHP_BIN="php"
for candidate in \
    /opt/cpanel/ea-php84/root/usr/bin/php \
    /opt/cpanel/ea-php83/root/usr/bin/php \
    /opt/alt/php84/usr/bin/php \
    /opt/alt/php83/usr/bin/php \
    /usr/local/bin/ea-php84 \
    /usr/local/bin/ea-php83 \
    php; do
    if command -v "$candidate" >/dev/null 2>&1; then
        PHP_VER=$("$candidate" -r "echo PHP_VERSION_ID;" 2>/dev/null || echo "0")
        if [ "$PHP_VER" -ge 80300 ] 2>/dev/null; then
            PHP_BIN="$candidate"
            break
        fi
    fi
done

echo "ℹ️ Menggunakan PHP CLI: $PHP_BIN ($($PHP_BIN -v | head -n 1))"

if [ ! -f .env ]; then
    echo "📄 Membuat file .env dari .env.example..."
    cp .env.example .env
fi

if grep -q "^APP_KEY=$" .env; then
    echo "🔑 Mengenerate APP_KEY..."
    "$PHP_BIN" -d display_errors=1 artisan key:generate --force
fi

echo "🗄️ Menjalankan migrasi & seeder database..."
"$PHP_BIN" -d display_errors=1 artisan migrate --seed --force

echo "🧹 Membersihkan & memperbarui cache..."
"$PHP_BIN" -d display_errors=1 artisan optimize:clear
"$PHP_BIN" -d display_errors=1 artisan config:cache
"$PHP_BIN" -d display_errors=1 artisan route:cache
"$PHP_BIN" -d display_errors=1 artisan view:cache

echo "✅ Update Ujian Online berhasil diterapkan!"
