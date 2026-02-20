#!/usr/bin/env bash
set -euo pipefail

DOCKER_MODE=false
if [[ "${1:-}" == "--docker" ]]; then
  DOCKER_MODE=true
  shift
fi

PRUNE_DAYS="${PRUNE_DAYS:-30}"

env_or_file() {
  local key="$1"
  local fallback="${2:-}"

  if [[ -n "${!key:-}" ]]; then
    printf '%s' "${!key}"
    return
  fi

  if [[ -f ".env" ]]; then
    local value
    value="$(grep -E "^${key}=" .env | tail -n1 | cut -d'=' -f2- || true)"
    value="${value%\"}"
    value="${value#\"}"
    if [[ -n "$value" ]]; then
      printf '%s' "$value"
      return
    fi
  fi

  printf '%s' "$fallback"
}

run_artisan() {
  if [[ "$DOCKER_MODE" == "true" ]]; then
    docker compose exec -T app php artisan "$@"
    return
  fi

  php artisan "$@"
}

create_docker_backup() {
  local db_name db_user db_password db_host db_port filename tmp_file target_file
  db_name="$(env_or_file DB_DATABASE choyxona)"
  db_user="$(env_or_file DB_USERNAME choyxona)"
  db_password="$(env_or_file DB_PASSWORD choyxona)"
  db_host="$(env_or_file DB_HOST 127.0.0.1)"
  db_port="$(env_or_file DB_PORT 3306)"
  filename="db-mysql-$(date +%Y%m%d-%H%M%S).sql.gz"
  target_file="storage/app/backups/databases/${filename}"

  tmp_file="$(mktemp)"
  trap 'rm -f "$tmp_file"' RETURN

  docker compose exec -T db sh -lc \
    "mysqldump --host='${db_host}' --port='${db_port}' --user='${db_user}' --password='${db_password}' --single-transaction --quick --lock-tables=false --no-tablespaces '${db_name}'" \
    | gzip -9 > "$tmp_file"

  docker compose exec -T app sh -lc \
    "mkdir -p storage/app/backups/databases && cat > '${target_file}'" < "$tmp_file"

  if [[ "$PRUNE_DAYS" =~ ^[0-9]+$ ]] && (( PRUNE_DAYS > 0 )); then
    docker compose exec -T app sh -lc \
      "find storage/app/backups/databases -type f -name 'db-*.sql.gz' -mtime +${PRUNE_DAYS} -delete"
  fi

  echo "Database backup created: ${target_file}"
}

find_latest_backup() {
  if [[ "$DOCKER_MODE" == "true" ]]; then
    docker compose exec -T app sh -lc \
      "ls -1t storage/app/backups/databases/db-*.sql.gz 2>/dev/null | head -n1"
    return
  fi

  ls -1t storage/app/backups/databases/db-*.sql.gz 2>/dev/null | head -n1 || true
}

test_backup_archive() {
  local backup_file="$1"

  if [[ "$DOCKER_MODE" == "true" ]]; then
    docker compose exec -T app sh -lc "gzip -t '$backup_file'"
    return
  fi

  gzip -t "$backup_file"
}

preview_backup_dump() {
  local backup_file="$1"

  if [[ "$DOCKER_MODE" == "true" ]]; then
    docker compose exec -T app sh -lc "gzip -dc '$backup_file' | head -n 5"
    return
  fi

  gzip -dc "$backup_file" | head -n 5
}

echo "Running database backup (prune-days=${PRUNE_DAYS})..."
if [[ "$DOCKER_MODE" == "true" ]]; then
  create_docker_backup
else
  run_artisan backup:database "--prune-days=${PRUNE_DAYS}"
fi

LATEST_BACKUP="$(find_latest_backup)"
if [[ -z "$LATEST_BACKUP" ]]; then
  echo "Backup smoke failed: no backup file found." >&2
  exit 1
fi

echo "Latest backup: ${LATEST_BACKUP}"
test_backup_archive "$LATEST_BACKUP"
echo "Archive integrity check: OK"

echo "Dump preview (first 5 lines):"
preview_backup_dump "$LATEST_BACKUP"

echo "Backup restore-smoke checks passed"
