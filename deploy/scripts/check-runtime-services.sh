#!/usr/bin/env bash
set -euo pipefail

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

check_queue_worker
check_scheduler

echo "Runtime service checks passed"
