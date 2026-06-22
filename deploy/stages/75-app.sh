#!/usr/bin/env bash
# Stage 75 — application filesystem layout (zero-downtime release model) and
# the production .env. CI deploys into releases/<ts> and flips the `current`
# symlink; .env and storage are shared across releases.
#
#   ${APP_DIR}/
#     releases/<timestamp>/   (each deploy)
#     current -> releases/...  (atomic symlink)
#     shared/.env             (secrets; symlinked into each release)
#     shared/storage/         (persistent uploads/logs; symlinked in)

DB_PASSWORD="$(resolve_secret DB_PASSWORD 32)"
REDIS_PASSWORD="$(resolve_secret REDIS_PASSWORD 40)"
APP_KEY="$(read_credential APP_KEY)"
if [[ -z "${APP_KEY}" ]]; then
  APP_KEY="base64:$(openssl rand -base64 32)"
  save_credential "APP_KEY" "${APP_KEY}"
fi

log "Creating app directory tree under ${APP_DIR}..."
install -d "${APP_DIR}"/{releases,shared}
install -d "${APP_DIR}"/shared/storage/{app/public,framework/{cache/data,sessions,views},logs}

# Production .env (shared across releases).
ENV_FILE="${APP_DIR}/shared/.env"
if [[ -f "${ENV_FILE}" ]] && [[ "${FORCE:-0}" != "1" ]]; then
  ok ".env already exists at ${ENV_FILE} (FORCE=1 to regenerate)."
else
  export APP_KEY APP_DOMAIN TIMEZONE DB_NAME DB_USER DB_PASSWORD REDIS_PASSWORD QUEUE_CONNECTION
  log "Writing production .env..."
  render_template "${TEMPLATES_DIR}/env.production" "${ENV_FILE}"
fi
chmod 640 "${ENV_FILE}"

# Ownership: deploy user owns the tree; www-data (php-fpm) shares via group.
chown -R "${DEPLOY_USER}:www-data" "${APP_DIR}"
chmod -R 775 "${APP_DIR}/shared/storage"
chmod g+s "${APP_DIR}/shared/storage"

# Default ACLs so new files written by php-fpm stay group-writable for deploys.
if command -v setfacl >/dev/null 2>&1; then
  setfacl -R  -m u:www-data:rwX -m u:"${DEPLOY_USER}":rwX "${APP_DIR}/shared/storage"
  setfacl -dR -m u:www-data:rwX -m u:"${DEPLOY_USER}":rwX "${APP_DIR}/shared/storage"
fi

# Deploy config consumed by the server-side deploy script.
cat > /etc/tlm-deploy.env <<EOF
APP_DIR=${APP_DIR}
PHP_VERSION=${PHP_VERSION}
DEPLOY_USER=${DEPLOY_USER}
KEEP_RELEASES=${KEEP_RELEASES}
APP_DOMAIN=${APP_DOMAIN}
EOF
chmod 644 /etc/tlm-deploy.env

# Install the server-side deploy script for CI to call.
install -d "${APP_DIR}/bin"
install -m 0755 -o "${DEPLOY_USER}" -g "${DEPLOY_USER}" \
  "${DEPLOY_ROOT}/deploy/deploy.sh" "${APP_DIR}/bin/deploy.sh"

ok "App layout ready. .env at ${ENV_FILE}; deploy script at ${APP_DIR}/bin/deploy.sh"
warn "No release deployed yet — push via CI/CD or run: sudo -u ${DEPLOY_USER} ${APP_DIR}/bin/deploy.sh <artifact.zip>"
