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
# restart/inspect workers) — least privilege rather than blanket NOPASSWD.
# Paths use /usr/bin (sudo matches the resolved absolute path on Ubuntu).
#
# supervisorctl is granted whole, without an argument list, because sudo 1.9.17+
# rejects wildcards in command arguments ("wildcards are not allowed in command
# arguments") — so `restart tlm-worker:*`, the form that restarts a numprocs
# group, cannot be expressed as a rule at all. Enumerating bare process names
# instead would hard-code numprocs (tlm-worker:tlm-worker_00, _01, ...) and
# silently stop matching the day QUEUE_WORKERS changes. supervisorctl only ever
# acts on programs defined in root-owned /etc/supervisor/conf.d, which this user
# cannot write, so the widened verb set does not widen what it can execute.
# ponytail: whole-binary grant; narrow it again if sudo restores arg wildcards.
SUDOERS_FILE="/etc/sudoers.d/90-${DEPLOY_USER}-deploy"
cat > "${SUDOERS_FILE}.new" <<EOF
# Restricted deploy automation for ${DEPLOY_USER} (used by CI/CD).
${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP_VERSION}-fpm, \\
  /usr/bin/systemctl reload nginx, \\
  /usr/bin/supervisorctl
EOF
chmod 440 "${SUDOERS_FILE}.new"
# Validate BEFORE installing: a malformed file in /etc/sudoers.d makes sudo
# refuse every rule on the box, and the operator may have no other way in.
if ! visudo -cf "${SUDOERS_FILE}.new" >/dev/null; then
  rm -f "${SUDOERS_FILE}.new"
  die "Generated sudoers for ${DEPLOY_USER} is invalid — left existing file untouched."
fi
mv "${SUDOERS_FILE}.new" "${SUDOERS_FILE}"

ok "Deploy user ${DEPLOY_USER} configured."
