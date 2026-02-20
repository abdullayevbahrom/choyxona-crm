#!/usr/bin/env bash
set -euo pipefail

APP_URL="${1:-${APP_URL:-http://127.0.0.1:8000}}"
APP_URL="${APP_URL%/}"
SMOKE_EMAIL="${SMOKE_EMAIL:-manager@choyxona.uz}"
SMOKE_PASSWORD="${SMOKE_PASSWORD:-password}"

TMP_DIR="$(mktemp -d)"
COOKIE_JAR="${TMP_DIR}/cookies.txt"
trap 'rm -rf "${TMP_DIR}"' EXIT

request_code() {
  local url="$1"
  shift
  curl -sS -o /dev/null -w "%{http_code}" "$@" "$url"
}

health_code="$(request_code "${APP_URL}/healthz")"
if [[ "$health_code" != "200" ]]; then
  echo "Smoke failed: /healthz returned ${health_code}" >&2
  exit 1
fi

echo "Smoke: /healthz OK"

login_page="${TMP_DIR}/login.html"
curl -sS -c "$COOKIE_JAR" -b "$COOKIE_JAR" "${APP_URL}/login" -o "$login_page"

csrf_token="$(grep -oE 'name="_token" value="[^"]+"' "$login_page" | head -n1 | sed -E 's/.*value="([^"]+)"/\1/')"
if [[ -z "$csrf_token" ]]; then
  echo "Smoke failed: unable to parse CSRF token from login page" >&2
  exit 1
fi

login_code="$(
  curl -sS -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
    -o "${TMP_DIR}/post-login.html" -w "%{http_code}" \
    -X POST "${APP_URL}/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    --data-urlencode "_token=${csrf_token}" \
    --data-urlencode "email=${SMOKE_EMAIL}" \
    --data-urlencode "password=${SMOKE_PASSWORD}"
)"

if [[ "$login_code" != "302" && "$login_code" != "303" && "$login_code" != "200" ]]; then
  echo "Smoke failed: login flow returned HTTP ${login_code}" >&2
  exit 1
fi

echo "Smoke: login OK (${SMOKE_EMAIL})"

for path in "/dashboard" "/reports" "/orders/history"; do
  code="$(request_code "${APP_URL}${path}" -c "$COOKIE_JAR" -b "$COOKIE_JAR")"
  if [[ "$code" != "200" ]]; then
    echo "Smoke failed: ${path} returned ${code}" >&2
    exit 1
  fi
  echo "Smoke: ${path} OK"
done

cards_code="$(request_code "${APP_URL}/dashboard/cards" -c "$COOKIE_JAR" -b "$COOKIE_JAR" -H "X-Requested-With: XMLHttpRequest")"
if [[ "$cards_code" != "200" ]]; then
  echo "Smoke failed: /dashboard/cards returned ${cards_code}" >&2
  exit 1
fi

echo "Smoke: /dashboard/cards OK"
echo "Web smoke checks passed for ${APP_URL}"
