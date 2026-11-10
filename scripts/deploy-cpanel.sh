#!/usr/bin/env bash
# Hira Moni — cPanel deploy for hiramoni.bynnas.com
#
#   bash /home/bynnsuou/repositories/Hiramoni-online-shop/scripts/deploy-cpanel.sh
#   SEED=1 bash .../scripts/deploy-cpanel.sh

set -euo pipefail

REPO="/home/bynnsuou/repositories/Hiramoni-online-shop"
# Real cPanel document root for this subdomain (NOT a whole-dir symlink — LiteSpeed 404s those)
WEB_ROOT="/home/bynnsuou/hiramoni.bynnas.com"
APP_URL="https://hiramoni.bynnas.com"
DB_DATABASE="bynnsuou_hiramoni"
DB_USERNAME="${DB_USERNAME:-bynnsuou_hiramoni}"
SEED="${SEED:-0}"

link_web_root() {
  echo "==> Link $WEB_ROOT → app public (LiteSpeed-safe)"
  if [[ -L "$WEB_ROOT" ]]; then
    rm -f "$WEB_ROOT"
  elif [[ -d "$WEB_ROOT" ]]; then
    # Keep first-time backup only if it still looks like an empty cPanel stub
    if [[ ! -f "$WEB_ROOT/index.php" ]]; then
      mv "$WEB_ROOT" "${WEB_ROOT}.bak-$(date +%Y%m%d%H%M%S)"
    fi
  fi
  mkdir -p "$WEB_ROOT"
  cat > "$WEB_ROOT/index.php" <<EOF
<?php
require '$REPO/public/index.php';
EOF
  cp -f "$REPO/public/.htaccess" "$WEB_ROOT/.htaccess"
  # LiteSpeed 404s most symlinks outside docroot — copy static dirs/files.
  rm -rf "$WEB_ROOT/build" "$WEB_ROOT/images"
  [[ -d "$REPO/public/build" ]] && cp -a "$REPO/public/build" "$WEB_ROOT/build"
  [[ -d "$REPO/public/images" ]] && cp -a "$REPO/public/images" "$WEB_ROOT/images"
  # Merge into docroot /storage (do not wipe merchant uploads). LiteSpeed cannot follow symlinks.
  mkdir -p "$WEB_ROOT/storage"
  if [[ -d "$REPO/storage/app/public" ]]; then
    cp -a "$REPO/storage/app/public/." "$WEB_ROOT/storage/"
  elif [[ -d "$REPO/public/storage" ]]; then
    cp -aL "$REPO/public/storage/." "$WEB_ROOT/storage/"
  fi
  for item in favicon.ico favicon.png favicon.svg favicon-32.png favicon-64.png robots.txt; do
    if [[ -e "$REPO/public/$item" ]]; then
      cp -aL "$REPO/public/$item" "$WEB_ROOT/$item"
    fi
  done
  rm -f "$WEB_ROOT/hot" "$REPO/public/hot"
  ls -la "$WEB_ROOT" | head -20
}

# Safe .env key setter (no multiline php -r — that breaks cPanel ea-php-cli)
env_set() {
  local key="$1"
  local value="$2"
  local tmp
  tmp="$(mktemp)"
  if grep -qE "^${key}=" .env 2>/dev/null; then
    awk -v k="$key" -v v="$value" 'BEGIN{FS=OFS="="} $1==k{$0=k"="v} {print}' .env >"$tmp"
    mv "$tmp" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
    rm -f "$tmp"
  fi
}

cd "$REPO"

echo "==> Pull latest"
git fetch origin
git checkout main
git pull --ff-only origin main

echo "==> Ensure .env"
if [[ ! -f .env ]]; then
  cp .env.example .env
fi

env_set "APP_NAME" "\"Hira Moni\""
env_set "APP_ENV" "production"
env_set "APP_DEBUG" "false"
env_set "APP_URL" "$APP_URL"
env_set "SEED_DEMO" "false"
env_set "DB_CONNECTION" "mysql"
env_set "DB_HOST" "127.0.0.1"
env_set "DB_PORT" "3306"
env_set "DB_DATABASE" "$DB_DATABASE"
env_set "DB_USERNAME" "$DB_USERNAME"
env_set "SESSION_DRIVER" "database"
env_set "CACHE_STORE" "database"
env_set "QUEUE_CONNECTION" "database"
env_set "LOG_LEVEL" "error"
env_set "SESSION_SECURE_COOKIE" "true"

# Password: env var, or invisible prompt if empty
current_pw="$(grep -E '^DB_PASSWORD=' .env | head -1 | cut -d= -f2- | tr -d '\"' || true)"
current_pw="${current_pw//\'/}"
if [[ -n "${DB_PASSWORD:-}" ]]; then
  env_set "DB_PASSWORD" "\"${DB_PASSWORD}\""
elif [[ -z "${current_pw}" || "${current_pw}" == "null" ]]; then
  echo ""
  echo "Database : $DB_DATABASE"
  echo "Username : $DB_USERNAME"
  echo -n "Enter MySQL password (hidden): "
  # shellcheck disable=SC2162
  read -s DB_PASSWORD_INPUT
  echo ""
  if [[ -z "${DB_PASSWORD_INPUT}" ]]; then
    echo "ERROR: password cannot be empty."
    exit 1
  fi
  env_set "DB_PASSWORD" "\"${DB_PASSWORD_INPUT}\""
  unset DB_PASSWORD_INPUT
else
  echo "==> Using existing DB_PASSWORD from .env"
fi

echo "==> Composer install"
composer install --no-dev --optimize-autoloader --no-interaction

if ! grep -qE '^APP_KEY=base64:' .env; then
  echo "==> Generate APP_KEY"
  php artisan key:generate --force
fi

echo "==> Build assets (if Node is available)"
if command -v npm >/dev/null 2>&1; then
  npm ci --omit=dev || npm install
  npm run build
else
  echo "    npm not found — using committed public/build assets"
fi

echo "==> Storage link + permissions"
php artisan storage:link --force 2>/dev/null || true
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

echo "==> Migrate"
php artisan migrate --force

if [[ "$SEED" == "1" ]]; then
  echo "==> Seed demo storefront (catalog, CMS, sales, blog)"
  php artisan db:seed --force || true
  php artisan db:seed --class=WebsiteSeeder --force || true
  php artisan db:seed --class=CosmeticsCatalogSeeder --force || true
  php artisan db:seed --class=BlogSeeder --force || true
  php artisan db:seed --class=DemoStorefrontSeeder --force || true
fi

echo "==> Publish packaged demo product photos"
php artisan storefront:publish-demo-media || true

echo "==> Ensure homepage deals are live"
php artisan storefront:ensure-deals || true

link_web_root

echo "==> Optimize"
php artisan optimize:clear || true
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear || true
php artisan event:clear || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
rm -f public/hot
# Ensure locale route exists in cached routes (storefront header depends on it)
php artisan route:list --name=locale.switch >/dev/null

echo ""
echo "Deploy complete → $APP_URL"
echo "Admin: $APP_URL/admin"
