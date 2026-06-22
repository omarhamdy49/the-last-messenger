#!/usr/bin/env bash
# Stage 60 — Redis: cache + session store (queue stays on MySQL — see README).
# Localhost-bound, password-protected, memory-capped with LRU eviction.

REDIS_PASSWORD="$(resolve_secret REDIS_PASSWORD 40)"

is_installed redis-server || apt_quiet install redis-server

export REDIS_PASSWORD REDIS_MAXMEMORY
log "Writing Redis overrides..."
# The Debian/Ubuntu package ships a single redis.conf; drop our overrides into a
# conf.d dir and `include` it (last-wins) so package upgrades don't clobber them.
mkdir -p /etc/redis/redis.conf.d
render_template "${TEMPLATES_DIR}/redis-overrides.conf" "/etc/redis/redis.conf.d/tlm.conf"
ensure_line "include /etc/redis/redis.conf.d/tlm.conf" /etc/redis/redis.conf

systemctl enable redis-server >/dev/null 2>&1 || true
systemctl restart redis-server

# Verify auth works.
if redis-cli -a "${REDIS_PASSWORD}" --no-auth-warning ping 2>/dev/null | grep -q PONG; then
  ok "Redis up, authenticated, maxmemory=${REDIS_MAXMEMORY} (allkeys-lru)."
else
  warn "Redis restarted but PING auth check failed — inspect /var/log/redis/."
fi
