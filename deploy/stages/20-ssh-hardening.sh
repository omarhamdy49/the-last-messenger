#!/usr/bin/env bash
# Stage 20 — SSH hardening. Disables root login + password auth (key-only).
# SAFETY: only runs after stage 15 has authorised a key for DEPLOY_USER.

AUTH_KEYS="$(getent passwd "${DEPLOY_USER}" | cut -d: -f6)/.ssh/authorized_keys"
if [[ ! -s "${AUTH_KEYS}" ]]; then
  die "No authorized_keys for ${DEPLOY_USER}; run stage 15-user first (won't lock you out)."
fi

DROPIN="/etc/ssh/sshd_config.d/99-tlm-hardening.conf"
backup_file "${DROPIN}"
cat > "${DROPIN}" <<EOF
# The Last Messenger — SSH hardening
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
ChallengeResponseAuthentication no
KbdInteractiveAuthentication no
UsePAM yes
PermitEmptyPasswords no
X11Forwarding no
MaxAuthTries 3
MaxSessions 5
LoginGraceTime 30
ClientAliveInterval 300
ClientAliveCountMax 2
AllowUsers ${DEPLOY_USER}
EOF

log "Validating sshd config..."
sshd -t -f /etc/ssh/sshd_config

# Ubuntu 24.04+ uses socket activation (ssh.socket); restart the right unit.
if systemctl list-unit-files | grep -q '^ssh\.service'; then
  systemctl restart ssh
else
  systemctl restart sshd
fi

warn "Root SSH + password login are now DISABLED. Verify a new session as '${DEPLOY_USER}' BEFORE closing this one."
ok "SSH hardened (key-only, ${DEPLOY_USER} only)."
