#!/usr/bin/env bash
# ===========================================================================
# provision.sh — orchestrator for The Last Messenger VPS provisioning.
#
#   Ubuntu 26.04 · 2 vCPU · 4GB RAM · 40GB · PHP 8.4 · MySQL 8 · Redis · Nginx
#
# Usage (as root):
#   cp config.env.example config.env   # then edit it
#   sudo ./provision.sh                 # run every stage in order
#   sudo ./provision.sh 40-php 65-nginx # run only specific stages
#   sudo ./provision.sh --list          # list stages
#   sudo FORCE=1 ./provision.sh 55-mysql# re-run a stage ignoring its done-mark
#
# Every stage is idempotent: safe to re-run the whole script at any time.
# ===========================================================================
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/lib/common.sh"

# --- Load config -----------------------------------------------------------
CONFIG_FILE="${SCRIPT_DIR}/config.env"
if [[ ! -f "${CONFIG_FILE}" ]]; then
  die "Missing ${CONFIG_FILE}. Run: cp config.env.example config.env && edit it."
fi
# shellcheck disable=SC1090
set -a; source "${CONFIG_FILE}"; set +a

STAGES_DIR="${SCRIPT_DIR}/stages"

list_stages() {
  find "${STAGES_DIR}" -maxdepth 1 -name '*.sh' | sort | while read -r f; do
    basename "$f" .sh
  done
}

run_stage() {
  local name="$1" file="${STAGES_DIR}/$1.sh"
  [[ -f "${file}" ]] || die "Unknown stage: ${name}"
  if is_done "${name}" && [[ "${FORCE:-0}" != "1" ]]; then
    ok "Stage ${name} already completed (FORCE=1 to re-run) — skipping."
    return 0
  fi
  log "──────── Stage: ${name} ────────"
  # shellcheck disable=SC1090
  source "${file}"
  mark_done "${name}"
  ok "Stage ${name} complete."
}

main() {
  require_root

  if [[ "${1:-}" == "--list" ]]; then list_stages; exit 0; fi

  # envsubst (from gettext-base) powers template rendering — ensure it exists.
  if ! command -v envsubst >/dev/null 2>&1; then
    apt-get update -y && apt_quiet install gettext-base
  fi

  local stages
  if [[ $# -gt 0 ]]; then
    stages="$*"
    EXPLICIT_STAGES=1
  else
    stages="$(list_stages)"
    EXPLICIT_STAGES=0
  fi
  export EXPLICIT_STAGES

  for s in ${stages}; do run_stage "${s}"; done

  echo
  ok "Provisioning finished."
  log "Credentials saved to ${CREDENTIALS_FILE} (root-only)."
  log "Next: push code via CI/CD (.github/workflows/deploy.yml) or run deploy/deploy.sh."
}

main "$@"
