#!/bin/bash
set -e

cd /var/www/html

echo "============================================"
echo "  User Manager - Dev Environment Setup"
echo "============================================"

# Install PHP dependencies
if [ ! -d "vendor" ]; then
  echo "→ Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist
else
  echo "✓ Composer dependencies already installed"
fi

# Install Node dependencies
if [ ! -d "node_modules" ]; then
  echo "→ Installing npm packages..."
  npm ci
else
  echo "✓ node_modules already present"
fi

# Copy .env if not present
if [ ! -f ".env" ]; then
  echo "→ Creating .env from .env.example..."
  cp .env.example .env
fi

# Patch .env file with Docker-specific values
# Laravel reads .env directly from disk (volume mount), so env vars from
# docker-compose alone are NOT enough — we must update the file itself.
echo "→ Patching .env for Docker environment..."

patch_env() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

patch_env "DB_CONNECTION"   "pgsql"
patch_env "DB_HOST"         "postgres"
patch_env "DB_PORT"         "5432"
patch_env "DB_DATABASE"     "user_manager"
patch_env "DB_USERNAME"     "postgres"
patch_env "DB_PASSWORD"     "postgres"
patch_env "SESSION_DRIVER"  "file"
patch_env "CACHE_DRIVER"    "file"
patch_env "CACHE_STORE"     "file"
patch_env "QUEUE_CONNECTION" "sync"
patch_env "LOG_CHANNEL"     "stderr"

# Clear caches using filesystem (safe BEFORE DB is ready — no Laravel boot required)
echo "→ Clearing caches..."
rm -f  bootstrap/cache/config.php
rm -f  bootstrap/cache/routes-v7.php
rm -f  bootstrap/cache/events.php
rm -rf storage/framework/cache/data/*
rm -f  storage/framework/views/*.php
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views

# Export patched .env vars into current bash session
# This ensures getenv() in the PHP postgres-wait check gets correct values
# even when no .env existed on the host at container start time.
set -a
# shellcheck disable=SC1091
. .env
set +a

# Generate APP_KEY if missing
if ! grep -q '^APP_KEY=base64:' .env; then
  echo "→ Generating application key..."
  php artisan key:generate --force
fi

# Wait for postgres to be ready
echo "→ Waiting for PostgreSQL..."
until php -r "
try {
  \$pdo = new PDO(
    'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD')
  );
  echo 'ok';
} catch (Exception \$e) {
  exit(1);
}
" 2>/dev/null | grep -q ok; do
  echo "  PostgreSQL not ready, retrying in 2s..."
  sleep 2
done
echo "✓ PostgreSQL is ready"

# Run migrations
echo "→ Running migrations..."
php artisan migrate --no-interaction --force

# Start Vite dev server in the background
echo "→ Starting Vite dev server for HMR..."
npm run dev &

echo ""
echo "============================================"
echo "  ✅ Setup complete!"
echo "  App: http://localhost:8000"
echo "  Vite HMR: listening on 5173"
echo "  Run import: docker compose -f docker-compose.dev.yml exec app php artisan users:import"
echo "============================================"
echo ""

# Start PHP dev server
exec php artisan serve --host=0.0.0.0 --port=8000
