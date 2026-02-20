#!/usr/bin/env bash
set -euo pipefail

ENV_FILE="${1:-.env}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Env file not found: $ENV_FILE" >&2
  exit 1
fi

required_vars=(
  APP_ENV
  APP_KEY
  APP_URL
  DB_CONNECTION
  DB_HOST
  DB_PORT
  DB_DATABASE
  DB_USERNAME
  DB_PASSWORD
  QUEUE_CONNECTION
  SESSION_DRIVER
  CACHE_STORE
)

missing=()

for var in "${required_vars[@]}"; do
  value="$(grep -E "^${var}=" "$ENV_FILE" | tail -n1 | cut -d'=' -f2- || true)"
  value="${value%\"}"
  value="${value#\"}"
  if [[ -z "$value" ]]; then
    missing+=("$var")
  fi
done

if (( ${#missing[@]} > 0 )); then
  echo "Missing required env vars in $ENV_FILE:" >&2
  printf ' - %s\n' "${missing[@]}" >&2
  exit 1
fi

echo "Env validation passed for $ENV_FILE"
