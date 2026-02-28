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

# Override .env with values from docker env vars (if set)
php artisan config:clear > /dev/null 2>&1 || true

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

# Build frontend assets
echo "→ Building frontend assets (Vite)..."
npm run build

echo ""
echo "============================================"
echo "  ✅ Setup complete!"
echo "  App: http://localhost:8000"
echo "  Run import: docker compose -f docker-compose.dev.yml exec app php artisan users:import"
echo "============================================"
echo ""

# Start PHP dev server
exec php artisan serve --host=0.0.0.0 --port=8000
