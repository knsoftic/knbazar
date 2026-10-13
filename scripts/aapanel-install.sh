#!/usr/bin/env bash
#
# One-command installer for KN Bazar on aaPanel (Nginx + PHP 8.4 + MySQL).
#
#   cd /www/wwwroot/website && git pull origin main && bash scripts/aapanel-install.sh
#
# What it does (each step is skipped when already done, so it is safe to re-run):
#   1. PHP: enables putenv/proc_open/proc_get_status/symlink, raises upload limits,
#      installs the fileinfo extension if missing, restarts PHP-FPM
#   2. .env: asks for the database details and writes production settings
#   3. Composer dependencies
#   4. Database tables, roles/permissions, default pages, Super Admin account
#   5. Storage link, Nginx (web root /public + Laravel rewrite + 110m uploads)
#   6. File ownership/permissions and Laravel caches
#
# Overrides: DOMAIN=knbazaar.com PHP_VER=84 APP_DIR=/www/wwwroot/website bash scripts/aapanel-install.sh
#
set -Eeuo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
DOMAIN="${DOMAIN:-knbazaar.com}"
PHP_VER="${PHP_VER:-84}"
PHP_DIR="/www/server/php/$PHP_VER"
PHP_BIN="$PHP_DIR/bin/php"
COMPOSER_BIN="${COMPOSER_BIN:-/usr/local/bin/composer}"
WEB_USER="${WEB_USER:-www}"
NGINX_BIN="/www/server/nginx/sbin/nginx"
NGINX_CONF="/www/server/nginx/conf/nginx.conf"
VHOST_DIR="/www/server/panel/vhost"

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
ok()   { printf '\033[0;32m    [ok] %s\033[0m\n' "$*"; }
warn() { printf '\033[0;33m    [!] %s\033[0m\n' "$*"; }
die()  { printf '\n\033[0;31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }
trap 'die "Step failed (line $LINENO). Read the message above, fix it, then run the same command again."' ERR

artisan() { "$PHP_BIN" artisan "$@" --no-interaction; }

# Write KEY=value into .env (value is written exactly as given).
set_env() {
    "$PHP_BIN" -n -r '
        [, $file, $key, $value] = $argv;
        $line = $key."=".$value;
        $env = file_get_contents($file);
        $pattern = "/^".preg_quote($key, "/")."=.*$/m";
        $env = preg_match($pattern, $env)
            ? preg_replace_callback($pattern, fn () => $line, $env, 1)
            : rtrim($env)."\n".$line."\n";
        file_put_contents($file, $env);
    ' "$APP_DIR/.env" "$1" "$2"
}

get_env() { { grep -E "^$1=" "$APP_DIR/.env" 2>/dev/null || true; } | head -n1 | cut -d= -f2- | sed -E "s/^['\"]//; s/['\"]$//"; }

[ "$(id -u)" -eq 0 ] || die "Please run as root."
[ -x "$PHP_BIN" ] || die "PHP not found at $PHP_BIN. Install PHP-8.4 from the aaPanel App Store first (or set PHP_VER=83)."
cd "$APP_DIR"
[ -f artisan ] || die "Laravel project not found in $APP_DIR."

echo "KN Bazar installer - app: $APP_DIR, domain: $DOMAIN, PHP: $("$PHP_BIN" -r 'echo PHP_VERSION;')"

# ---------------------------------------------------------------------------
step "1/6 Configuring PHP $PHP_VER"
# ---------------------------------------------------------------------------
INIS=()
for ini in "$PHP_DIR/etc/php.ini" "$PHP_DIR/etc/php-cli.ini"; do
    [ -f "$ini" ] && INIS+=("$ini")
done
[ "${#INIS[@]}" -gt 0 ] || die "No php.ini found in $PHP_DIR/etc."

for ini in "${INIS[@]}"; do
    [ -f "$ini.knbazar.bak" ] || cp "$ini" "$ini.knbazar.bak"
    mode="cli"; [ "$(basename "$ini")" = "php.ini" ] && mode="fpm"
    "$PHP_BIN" -n -r '
        [, $file, $mode] = $argv;
        $ini = file_get_contents($file);
        $enable = ["putenv", "proc_open", "proc_get_status", "symlink"];
        $ini = preg_replace_callback("/^(\s*disable_functions\s*=\s*)(.*)$/m", function ($m) use ($enable) {
            $fns = array_filter(array_map("trim", explode(",", $m[2])), fn ($f) => $f !== "" && ! in_array($f, $enable, true));
            return $m[1].implode(",", $fns);
        }, $ini);
        if ($mode === "fpm") {
            $settings = ["upload_max_filesize" => "100M", "post_max_size" => "110M", "memory_limit" => "256M", "max_execution_time" => "300", "max_input_time" => "300"];
            foreach ($settings as $key => $value) {
                $pattern = "/^\s*;?\s*".preg_quote($key, "/")."\s*=.*$/m";
                $line = $key." = ".$value;
                $ini = preg_match($pattern, $ini)
                    ? preg_replace_callback($pattern, fn () => $line, $ini, 1)
                    : rtrim($ini)."\n".$line."\n";
            }
        }
        file_put_contents($file, $ini);
    ' "$ini" "$mode"
    ok "$(basename "$ini"): putenv, proc_open, proc_get_status, symlink enabled$([ "$mode" = fpm ] && echo ', uploads up to 100M')"
done

build_fileinfo() {
    local src="$PHP_DIR/src/ext/fileinfo" ver tmp
    if [ ! -f "$src/config.m4" ]; then
        ver="$("$PHP_BIN" -r 'echo PHP_VERSION;')"
        tmp="$(mktemp -d)"
        echo "    downloading PHP $ver source..."
        curl -fsSL "https://www.php.net/distributions/php-$ver.tar.gz" -o "$tmp/php.tar.gz" || return 1
        tar -xzf "$tmp/php.tar.gz" -C "$tmp" "php-$ver/ext/fileinfo" || return 1
        src="$tmp/php-$ver/ext/fileinfo"
    fi
    echo "    compiling fileinfo (2-5 minutes)..."
    (
        cd "$src" \
        && "$PHP_DIR/bin/phpize" >/dev/null \
        && ./configure --with-php-config="$PHP_DIR/bin/php-config" >/dev/null \
        && make -j"$(nproc)" >/dev/null \
        && make install >/dev/null
    ) || return 1
}

MODULES="$("$PHP_BIN" -m)"
if ! grep -qix fileinfo <<<"$MODULES"; then
    EXT_DIR="$("$PHP_BIN" -r 'echo ini_get("extension_dir");')"
    if [ ! -f "$EXT_DIR/fileinfo.so" ]; then
        warn "fileinfo extension is not installed - building it now"
        build_fileinfo || die "Could not build fileinfo automatically. Install it in aaPanel: App Store > PHP-8.4 > Setting > Install extensions > fileinfo, then run this command again."
    fi
    for ini in "${INIS[@]}"; do
        grep -qE '^\s*extension\s*=\s*"?fileinfo(\.so)?"?\s*$' "$ini" || echo "extension = fileinfo.so" >> "$ini"
    done
    MODULES="$("$PHP_BIN" -m)"
fi
grep -qix fileinfo <<<"$MODULES" || die "fileinfo is still not loaded. Install it in aaPanel (App Store > PHP-8.4 > Setting > Install extensions > fileinfo) and run again."
ok "fileinfo extension loaded"

for ext in gd pdo_mysql mbstring openssl zip curl tokenizer xml ctype; do
    if grep -qix "$ext" <<<"$MODULES"; then ok "$ext"; else warn "$ext is missing - install it in aaPanel (App Store > PHP-8.4 > Setting > Install extensions)"; fi
done

if [ -x "/etc/init.d/php-fpm-$PHP_VER" ]; then
    /etc/init.d/php-fpm-"$PHP_VER" restart >/dev/null 2>&1 && ok "PHP-FPM $PHP_VER restarted" || warn "Restart PHP-$PHP_VER from aaPanel"
else
    warn "Restart PHP-$PHP_VER from aaPanel (App Store > PHP-8.4 > Restart)"
fi

# ---------------------------------------------------------------------------
step "2/6 Environment file (.env)"
# ---------------------------------------------------------------------------
HAS_SSL=0
VHOST="$VHOST_DIR/nginx/$DOMAIN.conf"
[ -f "$VHOST" ] && grep -q "ssl_certificate" "$VHOST" && HAS_SSL=1
SCHEME="http"; SECURE="false"
[ "$HAS_SSL" -eq 1 ] && { SCHEME="https"; SECURE="true"; }

if [ ! -f .env ]; then
    cp .env.example .env
    echo "    Enter the database you created in aaPanel (Databases)."
    read -rp "    Database name [knbazar]: " DB_NAME; DB_NAME="${DB_NAME:-knbazar}"
    read -rp "    Database user [$DB_NAME]: " DB_USER; DB_USER="${DB_USER:-$DB_NAME}"
    while :; do
        read -rsp "    Database password: " DB_PASS; echo
        [ -z "$DB_PASS" ] && { echo "    Password cannot be empty."; continue; }
        [[ "$DB_PASS" == *"'"* ]] && { echo "    Password must not contain a single quote ('). Change it in aaPanel and try again."; continue; }
        break
    done

    set_env APP_ENV production
    set_env APP_DEBUG false
    set_env APP_URL "$SCHEME://$DOMAIN"
    set_env LOG_LEVEL error
    set_env DB_CONNECTION mysql
    set_env DB_HOST 127.0.0.1
    set_env DB_PORT 3306
    set_env DB_DATABASE "$DB_NAME"
    set_env DB_USERNAME "$DB_USER"
    set_env DB_PASSWORD "'$DB_PASS'"
    set_env SESSION_SECURE_COOKIE "$SECURE"
    set_env QUEUE_CONNECTION sync
    ok ".env created ($SCHEME://$DOMAIN)"
else
    ok ".env already exists - keeping your values"
    if [ "$HAS_SSL" -eq 1 ] && [[ "$(get_env APP_URL)" == http://* ]]; then
        set_env APP_URL "https://$DOMAIN"
        set_env SESSION_SECURE_COOKIE true
        ok "SSL detected - switched APP_URL to https://$DOMAIN and enabled secure cookies"
    fi
fi

# ---------------------------------------------------------------------------
step "3/6 Composer dependencies"
# ---------------------------------------------------------------------------
if [ ! -x "$COMPOSER_BIN" ]; then
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    "$PHP_BIN" /tmp/composer-setup.php --quiet --install-dir="$(dirname "$COMPOSER_BIN")" --filename="$(basename "$COMPOSER_BIN")"
    rm -f /tmp/composer-setup.php
    ok "Composer installed to $COMPOSER_BIN"
fi
COMPOSER_ALLOW_SUPERUSER=1 "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction
ok "vendor/ installed"

if ! grep -qE '^APP_KEY=base64:' .env; then
    artisan key:generate --force
fi
ok "application key set"

# ---------------------------------------------------------------------------
step "4/6 Database"
# ---------------------------------------------------------------------------
artisan config:clear >/dev/null
artisan migrate --force || die "Database migration failed. Check DB_DATABASE, DB_USERNAME and DB_PASSWORD in $APP_DIR/.env (they must match aaPanel > Databases), then run again."
artisan db:seed --class=RolePermissionSeeder --force
artisan db:seed --class=PageSeeder --force
ok "tables, roles/permissions and default pages ready"

echo "    Super Admin account:"
for attempt in 1 2 3; do
    "$PHP_BIN" artisan marketplace:create-admin --skip-if-exists && break
    [ "$attempt" -eq 3 ] && die "Could not create the Super Admin. Run later: $PHP_BIN artisan marketplace:create-admin"
    echo "    Please try again."
done

# ---------------------------------------------------------------------------
step "5/6 Storage link and Nginx"
# ---------------------------------------------------------------------------
if [ -L public/storage ] && [ ! -e public/storage ]; then
    rm -f public/storage   # broken link from an earlier install
fi
if [ ! -e public/storage ]; then
    artisan storage:link >/dev/null 2>&1 || ln -s "$APP_DIR/storage/app/public" "$APP_DIR/public/storage"
fi
ok "public/storage -> storage/app/public"

if [ -f "$VHOST" ] && [ -x "$NGINX_BIN" ]; then
    REWRITE="$VHOST_DIR/rewrite/$DOMAIN.conf"
    cp "$VHOST" "$VHOST.knbazar.bak"
    [ -f "$REWRITE" ] && cp "$REWRITE" "$REWRITE.knbazar.bak"
    [ -f "$NGINX_CONF" ] && cp "$NGINX_CONF" "$NGINX_CONF.knbazar.bak"

    # Web root -> /public
    sed -i -E "s#^([[:space:]]*root[[:space:]]+)${APP_DIR}/?;#\1${APP_DIR}/public;#" "$VHOST"
    # Laravel URL rewrite (aaPanel includes this file in the site config)
    mkdir -p "$(dirname "$REWRITE")"
    printf 'location / {\n    try_files $uri $uri/ /index.php?$query_string;\n}\n' > "$REWRITE"
    # Allow product uploads up to 110 MB
    [ -f "$NGINX_CONF" ] && sed -i -E 's/client_max_body_size[[:space:]]+[0-9]+[kKmMgG]?;/client_max_body_size 110m;/' "$NGINX_CONF"

    if "$NGINX_BIN" -t >/dev/null 2>&1; then
        "$NGINX_BIN" -s reload
        ok "Nginx: root ${APP_DIR}/public, Laravel rewrite, client_max_body_size 110m"
    else
        cp "$VHOST.knbazar.bak" "$VHOST"
        [ -f "$REWRITE.knbazar.bak" ] && cp "$REWRITE.knbazar.bak" "$REWRITE"
        [ -f "$NGINX_CONF.knbazar.bak" ] && cp "$NGINX_CONF.knbazar.bak" "$NGINX_CONF"
        warn "Nginx config test failed - changes reverted. In aaPanel set Site > Running directory = /public and URL rewrite = laravel5."
    fi
else
    warn "Nginx site config for $DOMAIN not found. In aaPanel set Site > Running directory = /public and URL rewrite = laravel5."
fi

# ---------------------------------------------------------------------------
step "6/6 Caches and permissions"
# ---------------------------------------------------------------------------
artisan optimize:clear >/dev/null
artisan optimize >/dev/null
chown -R "$WEB_USER":"$WEB_USER" "$APP_DIR"
chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod 640 "$APP_DIR/.env"
chmod +x "$APP_DIR/scripts/"*.sh
ok "caches built, files owned by $WEB_USER"

trap - ERR
printf '\n\033[1;32mKN Bazar is installed: %s://%s\033[0m\n' "$SCHEME" "$DOMAIN"
echo "  Admin panel: $SCHEME://$DOMAIN/admin  (sign in with the Super Admin above)"
if [ "$HAS_SSL" -eq 0 ]; then
    echo
    echo "  Next: aaPanel > Website > $DOMAIN > SSL > Let's Encrypt (knbazaar.com + www) > Force HTTPS,"
    echo "        then run this same command again to switch the site to https."
fi
echo "  Later updates: bash $APP_DIR/scripts/aapanel-deploy.sh"
