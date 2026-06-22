#!/usr/bin/env bash
# Stage 50 — Node.js 20 LTS (for `npm ci && npm run build` of Vite assets).
# Assets are normally built in CI; Node on the server is the fallback path
# and keeps the box self-sufficient.

NODE_MAJOR="20"

if command -v node >/dev/null 2>&1 && [[ "$(node -v)" == v${NODE_MAJOR}.* ]]; then
  ok "Node.js present: $(node -v)"
else
  log "Installing Node.js ${NODE_MAJOR} LTS (NodeSource)..."
  install -d -m 0755 /etc/apt/keyrings
  curl -fsSL "https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key" \
    | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
  chmod a+r /etc/apt/keyrings/nodesource.gpg
  echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_MAJOR}.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list
  wait_apt_lock
  apt-get update -y
  apt_quiet install nodejs
  ok "Node.js installed: $(node -v), npm $(npm -v)"
fi
