#!/bin/sh
set -eu

: "${DB_HOST:=mysql}"
: "${DB_PORT:=3306}"

echo "[entrypoint] waiting for mysql at ${DB_HOST}:${DB_PORT}..."
until nc -z "${DB_HOST}" "${DB_PORT}" >/dev/null 2>&1; do
  sleep 2
done
echo "[entrypoint] mysql reachable"

cp /var/www/html/.env.docker /var/www/html/.env

if [ -z "${APP_KEY:-}" ]; then
  echo "[entrypoint] APP_KEY not set via environment - generating one into .env (grab it and set CMS_APP_KEY so it persists across deploys)"
  php artisan key:generate --force
fi

php artisan migrate --force
php artisan db:seed --class=WebsiteSettingsSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

exec "$@"
