#!/usr/bin/env bash
#
# Cloud Agent bootstrap for the Laravel application.
# Idempotent: safe to run repeatedly and against a cached/partially prepared VM.

set -euo pipefail

cd "$(dirname "$0")/.."

PHP_PACKAGES=(
  php8.3-cli
  php8.3-mbstring
  php8.3-xml
  php8.3-sqlite3
  php8.3-curl
  php8.3-bcmath
  php8.3-gd
  php8.3-zip
  php8.3-intl
  php8.3-gmp
)

# 1. System packages: PHP 8.3 runtime + extensions.
if ! command -v php >/dev/null 2>&1; then
  sudo DEBIAN_FRONTEND=noninteractive apt-get update -qq
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq unzip "${PHP_PACKAGES[@]}"
fi

# 2. Composer (system-wide).
if ! command -v composer >/dev/null 2>&1; then
  curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/tmp --filename=composer
  sudo mv /tmp/composer /usr/local/bin/composer
fi

# 3. PHP dependencies.
composer install --no-interaction --no-progress

# 4. Node dependencies.
npm install --no-audit --no-fund

# 5. Environment file. .env is gitignored, so generate a local dev copy when absent.
if [ ! -f .env ]; then
  cat > .env <<'ENV'
APP_NAME=Hemso
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=sv
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=sv_SE

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"

# Local-only QR time-clock station tokens (arbitrary dev values).
TIME_CLOCK_TOKEN_ENTRANCE=local-entrance-token
TIME_CLOCK_TOKEN_RESTAURANT=local-restaurant-token
ENV
fi

# 6. Application key.
if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

# 7. SQLite database file.
touch database/database.sqlite

# 8. Migrate and seed demo data (admin/host/guide/restaurant login accounts).
php artisan migrate --force --seed

# 9. Public storage symlink for uploaded images.
php artisan storage:link 2>/dev/null || true

# 10. Build frontend assets.
npm run build

echo "Cloud Agent environment ready."
