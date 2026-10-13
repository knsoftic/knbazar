#!/usr/bin/env bash
#
# Update a KN Bazar installation on aaPanel from GitHub.
# Usage (as root):  bash /www/wwwroot/website/scripts/aapanel-deploy.sh
#
# Override defaults with environment variables, e.g.
#   APP_DIR=/www/wwwroot/website PHP_BIN=/www/server/php/84/bin/php BRANCH=main bash scripts/aapanel-deploy.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-/www/wwwroot/website}"
PHP_BIN="${PHP_BIN:-/www/server/php/84/bin/php}"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer}"
BRANCH="${BRANCH:-main}"
WEB_USER="${WEB_USER:-www}"

cd "$APP_DIR"

echo "==> Putting the site into maintenance mode"
"$PHP_BIN" artisan down --retry=30 || true

# Bring the site back up even if a step below fails.
trap '"$PHP_BIN" artisan up || true' EXIT

echo "==> Pulling $BRANCH"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "==> Installing PHP dependencies"
COMPOSER_ALLOW_SUPERUSER=1 "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction

echo "==> Running migrations"
"$PHP_BIN" artisan migrate --force --no-interaction

echo "==> Syncing roles/permissions and default pages (never overwrites edits)"
"$PHP_BIN" artisan db:seed --class=RolePermissionSeeder --force --no-interaction
"$PHP_BIN" artisan db:seed --class=PageSeeder --force --no-interaction

echo "==> Rebuilding caches"
"$PHP_BIN" artisan optimize:clear --no-interaction
"$PHP_BIN" artisan optimize --no-interaction

echo "==> Fixing ownership and permissions"
chown -R "$WEB_USER":"$WEB_USER" "$APP_DIR"
chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "==> Done"
