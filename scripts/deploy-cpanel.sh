#!/usr/bin/env bash
# Hira Moni — cPanel deploy for hiramoni.bynnas.com
# Repo:  /home/bynnsuou/repositories/Hiramoni-online-shop
# Web:   /home/bynnsuou/public_html/gadget/maks-gadget/public  (symlinked → repo/public)
# DB:    bynnsuou_hiramoni
#
# Usage (SSH / cPanel Terminal):
#   bash /home/bynnsuou/repositories/Hiramoni-online-shop/scripts/deploy-cpanel.sh
# First run only (seed beauty demo):
#   SEED=1 bash /home/bynnsuou/repositories/Hiramoni-online-shop/scripts/deploy-cpanel.sh

set -euo pipefail

REPO="/home/bynnsuou/repositories/Hiramoni-online-shop"
WEB_PUBLIC="/home/bynnsuou/public_html/gadget/maks-gadget/public"
APP_URL="https://hiramoni.bynnas.com"
DB_DATABASE="bynnsuou_hiramoni"
# cPanel MySQL user that has access to bynnsuou_hiramoni (change if yours differs)
DB_USERNAME="${DB_USERNAME:-bynnsuou_hiramoni}"
SEED="${SEED:-0}"

cd "$REPO"

echo "==> Pull latest"
git fetch origin
git checkout main
git pull --ff-only origin main

echo "==> Ensure .env"
if [[ ! -f .env ]]; then
  cp .env.example .env
  php -r "file_exists('.env') && copy('.env', '.env');" 2>/dev/null || true
fi

# Production env (does not overwrite an existing DB password)
php -r '
$env = file_get_contents(".env");
$set = function(string $key, string $value) use (&$env): void {
    $line = $key."=".(preg_match("/\\s/", $value) ? "\"".$value."\"" : $value);
    if (preg_match("/^{$key}=.*/m", $env)) {
        $env = preg_replace("/^{$key}=.*/m", $line, $env);
    } else {
        $env .= "\n".$line."\n";
    }
};
$set("APP_NAME", "Hira Moni");
$set("APP_ENV", "production");
$set("APP_DEBUG", "false");
$set("APP_URL", getenv("APP_URL") ?: "https://hiramoni.bynnas.com");
$set("SEED_DEMO", "false");
$set("DB_CONNECTION", "mysql");
$set("DB_HOST", "127.0.0.1");
$set("DB_PORT", "3306");
$set("DB_DATABASE", getenv("DB_DATABASE") ?: "bynnsuou_hiramoni");
$set("DB_USERNAME", getenv("DB_USERNAME") ?: "bynnsuou_hiramoni");
$set("SESSION_DRIVER", "database");
$set("CACHE_STORE", "database");
$set("QUEUE_CONNECTION", "database");
$set("LOG_LEVEL", "error");
$set("SESSION_SECURE_COOKIE", "true");
file_put_contents(".env", $env);
' APP_URL="$APP_URL" DB_DATABASE="$DB_DATABASE" DB_USERNAME="$DB_USERNAME"

# If DB password is still empty, stop with a clear message
if grep -qE '^DB_PASSWORD=\s*$' .env || grep -qE '^DB_PASSWORD=$' .env || grep -qE '^DB_PASSWORD=null$' .env; then
  echo ""
  echo "ERROR: Set DB_PASSWORD in $REPO/.env then re-run this script."
  echo "  Database : $DB_DATABASE"
  echo "  Username : $DB_USERNAME"
  echo "  Example  : DB_PASSWORD=your_cpanel_mysql_password"
  exit 1
fi

if ! grep -qE '^APP_KEY=base64:' .env; then
  echo "==> Generate APP_KEY"
  php artisan key:generate --force
fi

echo "==> Composer install"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Build assets (if Node is available)"
if command -v npm >/dev/null 2>&1; then
  npm ci --omit=dev || npm install
  npm run build
else
  echo "    npm not found — using committed public/build assets"
fi

echo "==> Storage link + permissions"
php artisan storage:link --force || true
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

echo "==> Migrate"
php artisan migrate --force

if [[ "$SEED" == "1" ]]; then
  echo "==> Seed beauty catalog + blog (first deploy)"
  # ProductionSeeder / DemoSeeder paths used by this project
  php artisan db:seed --force || true
  php artisan db:seed --class=CosmeticsCatalogSeeder --force || true
  php artisan db:seed --class=BlogSeeder --force || true
fi

echo "==> Point domain document root at this app"
# Keep a backup of the old public folder once, then symlink to this repo's public/
if [[ -d "$WEB_PUBLIC" && ! -L "$WEB_PUBLIC" ]]; then
  BACKUP="${WEB_PUBLIC}.bak-$(date +%Y%m%d%H%M%S)"
  echo "    Moving old public → $BACKUP"
  mv "$WEB_PUBLIC" "$BACKUP"
fi
PARENT="$(dirname "$WEB_PUBLIC")"
mkdir -p "$PARENT"
ln -sfn "$REPO/public" "$WEB_PUBLIC"
echo "    $WEB_PUBLIC → $REPO/public"

echo "==> Optimize"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Remove Vite HMR marker if present (breaks production CSS)
rm -f public/hot

echo ""
echo "Deploy complete → $APP_URL"
echo "Admin: $APP_URL/admin  (set ADMIN_* in .env if you seeded)"
