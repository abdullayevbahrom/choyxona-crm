#!/usr/bin/env bash
set -euo pipefail

check_docker_runtime() {
  if ! command -v docker >/dev/null 2>&1; then
    return 1
  fi

  if ! docker compose ps --format json >/dev/null 2>&1; then
    return 1
  fi

  local services_json
  services_json="$(docker compose ps --format json || true)"
  if [[ -z "$services_json" ]]; then
    return 1
  fi

  local ok=0
  for service in app worker scheduler; do
    if grep -q "\"Service\":\"${service}\"" <<<"$services_json" && grep -q "\"Service\":\"${service}\".*\"State\":\"running\"" <<<"$services_json"; then
      echo "Docker runtime check: ${service} is running"
    else
      echo "Docker runtime check failed: ${service} is not running" >&2
      ok=1
    fi
  done

  if (( ok != 0 )); then
    return 1
  fi

  echo "Docker runtime checks passed"
  return 0
}

check_queue_worker() {
  if command -v supervisorctl >/dev/null 2>&1; then
    local supervisor_output
    supervisor_output="$(supervisorctl status 'choyxona-worker:*' 2>/dev/null || true)"
    if [[ -n "$supervisor_output" ]] && grep -q "RUNNING" <<<"$supervisor_output"; then
      echo "Queue worker check: supervisor RUNNING"
      return 0
    fi
  fi

  if pgrep -f "artisan queue:work" >/dev/null 2>&1; then
    echo "Queue worker check: artisan queue:work process found"
    return 0
  fi

  echo "Queue worker check failed: no running queue worker detected" >&2
  return 1
}

check_scheduler() {
  if command -v supervisorctl >/dev/null 2>&1; then
    local scheduler_output
    scheduler_output="$(supervisorctl status choyxona-scheduler 2>/dev/null || true)"
    if [[ -n "$scheduler_output" ]] && grep -q "RUNNING" <<<"$scheduler_output"; then
      echo "Scheduler check: supervisor choyxona-scheduler RUNNING"
      return 0
    fi
  fi

  if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet choyxona-scheduler.timer; then
      echo "Scheduler check: choyxona-scheduler.timer is active"
      return 0
    fi
  fi

  if pgrep -f "artisan schedule:work" >/dev/null 2>&1; then
    echo "Scheduler check: artisan schedule:work process found"
    return 0
  fi

  echo "Scheduler check failed: no active scheduler timer/process detected" >&2
  return 1
}

if [[ "${1:-}" == "--docker" ]]; then
  check_docker_runtime
  exit 0
fi

if check_docker_runtime; then
  exit 0
fi

check_queue_worker
check_scheduler

echo "Runtime service checks passed"
