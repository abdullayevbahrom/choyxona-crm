#!/usr/bin/env bash
set -euo pipefail

DOCKER_MODE=false
if [[ "${1:-}" == "--docker" ]]; then
  DOCKER_MODE=true
  shift
fi

APP_URL="${1:-${APP_URL:-http://127.0.0.1:8000}}"

run_artisan() {
  if [[ "$DOCKER_MODE" == "true" ]]; then
    docker compose exec -T app php artisan "$@"
    return
  fi

  php artisan "$@"
}

run_artisan about --only=environment
run_artisan queue:monitor default --max=100 || true
run_artisan monitor:system-health

PENDING_MIGRATIONS="$(run_artisan migrate:status --pending --no-ansi || true)"
if grep -q "Pending" <<<"$PENDING_MIGRATIONS"; then
  echo "Pending migrations detected:" >&2
  echo "$PENDING_MIGRATIONS" >&2
  exit 1
fi
echo "Migrations check: no pending migrations"

HEALTH_JSON="$(curl -fsS --max-time 10 "${APP_URL%/}/healthz")"
echo "Health: $HEALTH_JSON"

HEALTH_JSON="$HEALTH_JSON" php -r '
$json = getenv("HEALTH_JSON");
$data = json_decode($json, true);
if (!is_array($data)) {
    fwrite(STDERR, "Health parse failed: invalid JSON\n");
    exit(1);
}
$statusOk = ($data["status"] ?? null) === "ok";
$databaseOk = ($data["checks"]["database"] ?? null) === true;
$storageOk = ($data["checks"]["storage"] ?? null) === true;
$hasQueueBacklog = array_key_exists("queue_backlog", $data["checks"] ?? []);
$hasDiskFree = array_key_exists("disk_free", $data["checks"] ?? []);
if (!($statusOk && $databaseOk && $storageOk && $hasQueueBacklog && $hasDiskFree)) {
    fwrite(STDERR, "Health validation failed\n");
    exit(1);
}
'

if [[ "$DOCKER_MODE" == "true" ]]; then
  ./deploy/scripts/check-runtime-services.sh --docker
else
  ./deploy/scripts/check-runtime-services.sh
fi
./deploy/scripts/smoke-web.sh "$APP_URL"

echo "Post-deploy verification passed for ${APP_URL%/}"
