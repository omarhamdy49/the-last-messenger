#!/usr/bin/env bash
# Stage 05 — base system: update, timezone, locale, hostname, core packages.

log "Updating package index and upgrading base system..."
apt-get update -y
apt_quiet upgrade

log "Installing core utilities..."
apt_quiet install \
  ca-certificates curl wget gnupg lsb-release apt-transport-https \
  software-properties-common git unzip zip tar acl \
  htop vim ncdu jq rsync gettext-base \
  logrotate cron

log "Setting timezone -> ${TIMEZONE}"
timedatectl set-timezone "${TIMEZONE}" || warn "Could not set timezone"

log "Configuring locale -> ${SYSTEM_LOCALE}"
if ! locale -a 2>/dev/null | grep -qi "${SYSTEM_LOCALE//-/}"; then
  locale-gen "${SYSTEM_LOCALE}" || true
fi
update-locale LANG="${SYSTEM_LOCALE}" LC_ALL="${SYSTEM_LOCALE}" || true

if [[ -n "${HOSTNAME:-}" ]]; then
  log "Setting hostname -> ${HOSTNAME}"
  hostnamectl set-hostname "${HOSTNAME}" || warn "Could not set hostname"
  ensure_line "127.0.1.1 ${HOSTNAME}" /etc/hosts
fi

# Ensure cron is enabled (scheduler fallback / system jobs).
systemctl enable --now cron >/dev/null 2>&1 || true

ok "Base system ready."
