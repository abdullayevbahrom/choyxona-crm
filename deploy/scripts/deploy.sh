#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
HEALTHCHECK_URL="${1:-}"
cd "$APP_DIR"

./deploy/scripts/validate-env.sh .env

php artisan down || true
trap 'php artisan up || true' EXIT

git pull --ff-only
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart

./deploy/scripts/post-deploy-verify.sh "$HEALTHCHECK_URL"
