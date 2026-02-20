#!/usr/bin/env bash
set -euo pipefail

APP_URL="${1:-${APP_URL:-http://127.0.0.1:8000}}"

php artisan about --only=environment
php artisan queue:monitor default --max=100 || true

HEALTH_JSON="$(curl -fsS --max-time 10 "${APP_URL%/}/healthz")"
echo "Health: $HEALTH_JSON"

echo "$HEALTH_JSON" | grep -q '"status":"ok"'

echo "Post-deploy verification passed for ${APP_URL%/}"
