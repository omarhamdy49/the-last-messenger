#!/usr/bin/env bash
# Stage 00 — preflight: validate environment, config, and OS.
# Sourced by provision.sh (common.sh + config already loaded).

require_root

# Required config values that have no safe default.
[[ -n "${APP_DOMAIN:-}" ]]       || die "APP_DOMAIN is required in config.env"
[[ -n "${DEPLOY_USER:-}" ]]      || die "DEPLOY_USER is required in config.env"
[[ -n "${PHP_VERSION:-}" ]]      || die "PHP_VERSION is required in config.env"
if [[ -z "${DEPLOY_USER_PUBKEY:-}" ]]; then
  warn "DEPLOY_USER_PUBKEY is empty — SSH key-based login for '${DEPLOY_USER}' will NOT be configured."
  warn "CI/CD over SSH and key-only SSH hardening depend on it. Set it in config.env to continue safely."
  die  "Refusing to proceed without DEPLOY_USER_PUBKEY (avoids locking yourself out)."
fi

# OS check — warn (don't hard-fail) so the scripts remain usable on 24.04+.
if [[ -r /etc/os-release ]]; then
  . /etc/os-release
  log "Detected OS: ${PRETTY_NAME:-unknown}"
  [[ "${ID:-}" == "ubuntu" ]] || warn "Designed for Ubuntu; '${ID:-?}' detected — proceed with care."
fi

# Resource sanity (tuned for 4GB/2vCPU/40GB).
mem_mb=$(awk '/MemTotal/ {printf "%d", $2/1024}' /proc/meminfo)
disk_gb=$(df -BG --output=avail / | tail -1 | tr -dc '0-9')
cpus=$(nproc)
log "Resources: ${cpus} vCPU · ${mem_mb}MB RAM · ${disk_gb}GB free on /"
(( mem_mb >= 1800 )) || warn "Less RAM than expected (${mem_mb}MB) — tuning assumes ~4GB."
(( disk_gb >= 10 ))  || warn "Low free disk (${disk_gb}GB)."

mkdir -p "${STATE_DIR}"
ok "Preflight checks passed."
