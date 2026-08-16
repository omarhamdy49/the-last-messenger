#!/usr/bin/env bash
# Stage 15 — create the non-root deploy user (owns the app, used by CI/CD).

if id "${DEPLOY_USER}" >/dev/null 2>&1; then
  ok "User ${DEPLOY_USER} already exists."
else
  log "Creating user ${DEPLOY_USER}..."
  adduser --disabled-password --gecos "" "${DEPLOY_USER}"
fi

# sudo for ops; www-data group so it shares file perms with php-fpm/nginx.
usermod -aG sudo,www-data "${DEPLOY_USER}"

# Authorise the CI/SSH public key.
USER_HOME="$(getent passwd "${DEPLOY_USER}" | cut -d: -f6)"
install -d -m 700 -o "${DEPLOY_USER}" -g "${DEPLOY_USER}" "${USER_HOME}/.ssh"
AUTH_KEYS="${USER_HOME}/.ssh/authorized_keys"
touch "${AUTH_KEYS}"
ensure_line "${DEPLOY_USER_PUBKEY}" "${AUTH_KEYS}"
chmod 600 "${AUTH_KEYS}"
chown "${DEPLOY_USER}:${DEPLOY_USER}" "${AUTH_KEYS}"
ok "Authorised SSH key for ${DEPLOY_USER}."

# Passwordless sudo only for the deploy verbs CI needs (reload services,
# restart workers) — least privilege rather than blanket NOPASSWD.
# Paths use /usr/bin (sudo matches the resolved absolute path on Ubuntu).
cat > "/etc/sudoers.d/90-${DEPLOY_USER}-deploy" <<EOF
# Restricted deploy automation for ${DEPLOY_USER} (used by CI/CD).
${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP_VERSION}-fpm, \\
  /usr/bin/systemctl reload nginx, \\
  /usr/bin/supervisorctl restart all, \\
  /usr/bin/supervisorctl restart tlm-scheduler, \\
  /usr/bin/supervisorctl reread, \\
  /usr/bin/supervisorctl update, \\
  /usr/bin/supervisorctl status, \\
  /usr/bin/supervisorctl status *
EOF
chmod 440 "/etc/sudoers.d/90-${DEPLOY_USER}-deploy"
visudo -cf "/etc/sudoers.d/90-${DEPLOY_USER}-deploy" >/dev/null

ok "Deploy user ${DEPLOY_USER} configured."
