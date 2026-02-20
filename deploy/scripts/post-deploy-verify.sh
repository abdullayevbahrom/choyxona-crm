#!/usr/bin/env bash
set -euo pipefail

APP_URL="${1:-${APP_URL:-http://127.0.0.1:8000}}"

php artisan about --only=environment
php artisan queue:monitor default --max=100 || true

PENDING_MIGRATIONS="$(php artisan migrate:status --pending --no-ansi || true)"
if grep -q "Pending" <<<"$PENDING_MIGRATIONS"; then
  echo "Pending migrations detected:" >&2
  echo "$PENDING_MIGRATIONS" >&2
  exit 1
fi
echo "Migrations check: no pending migrations"

HEALTH_JSON="$(curl -fsS --max-time 10 "${APP_URL%/}/healthz")"
echo "Health: $HEALTH_JSON"

echo "$HEALTH_JSON" | grep -q '"status":"ok"'

./deploy/scripts/check-runtime-services.sh
./deploy/scripts/smoke-web.sh "$APP_URL"

echo "Post-deploy verification passed for ${APP_URL%/}"
