#!/usr/bin/env bash
# Stage 90 — health check: verify every service and the app endpoint.
# Non-fatal: reports a summary so you can see exactly what's healthy.

REDIS_PASSWORD="$(resolve_secret REDIS_PASSWORD 40)"
# This stage is diagnostic: never let a failing probe abort the run.
set +e
trap - ERR

fails=0
check() { # name, command...
  local name="$1"; shift
  if "$@" >/dev/null 2>&1; then ok "${name}"; else err "${name}"; fails=$((fails+1)); fi
}

echo "──────── Service health ────────"
check "swap active"            bash -c "swapon --show | grep -q ."
check "php${PHP_VERSION}-fpm"  systemctl is-active --quiet "php${PHP_VERSION}-fpm"
check "nginx"                  systemctl is-active --quiet nginx
check "mysql"                  bash -c "systemctl is-active --quiet mysql || systemctl is-active --quiet mysqld"
check "redis"                  systemctl is-active --quiet redis-server
check "supervisor"            systemctl is-active --quiet supervisor
check "fail2ban"               systemctl is-active --quiet fail2ban
check "ufw enabled"            bash -c "ufw status | grep -q 'Status: active'"

echo "──────── Connectivity ────────"
check "MySQL query"            bash -c "mysql -e 'SELECT 1' '${DB_NAME}'"
check "Redis auth ping"        bash -c "redis-cli -a '${REDIS_PASSWORD}' --no-auth-warning ping | grep -q PONG"

echo "──────── Application ────────"
if [[ -e "${APP_DIR}/current/artisan" ]]; then
  check "supervisor workers up" bash -c "supervisorctl status tlm-worker:* | grep -q RUNNING"
  check "scheduler up"          bash -c "supervisorctl status tlm-scheduler | grep -q RUNNING"
  # Local HTTPS app probe (resolve domain to loopback to avoid external DNS).
  code=$(curl -kso /dev/null -w '%{http_code}' --resolve "${APP_DOMAIN}:443:127.0.0.1" "https://${APP_DOMAIN}/" 2>/dev/null || echo 000)
  if [[ "${code}" =~ ^(200|302|301)$ ]]; then ok "app responds (HTTP ${code})"; else err "app HTTP ${code}"; fails=$((fails+1)); fi
else
  warn "No release deployed yet — skipping app/worker checks. Deploy, then re-run: sudo ./provision.sh 90-healthcheck (FORCE=1)."
fi

echo "────────────────────────────────"
if (( fails == 0 )); then
  ok "All health checks passed. 🎉"
else
  warn "${fails} check(s) failed — review the lines marked ✗ above."
fi

# Restore strict mode for the orchestrator (this stage is sourced).
trap on_error ERR
set -e
