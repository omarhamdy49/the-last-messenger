#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# common.sh — shared helpers for every provisioning stage.
# Sourced by provision.sh and each stages/*.sh. Never executed directly.
# ---------------------------------------------------------------------------
set -Eeuo pipefail

# --- Paths -----------------------------------------------------------------
DEPLOY_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
export DEPLOY_ROOT
TEMPLATES_DIR="${DEPLOY_ROOT}/templates"
CREDENTIALS_FILE="${CREDENTIALS_FILE:-/root/.tlm-credentials}"
STATE_DIR="${STATE_DIR:-/var/lib/tlm-provision}"

# --- Colours / logging -----------------------------------------------------
if [[ -t 1 ]]; then
  C_RESET="\033[0m"; C_INFO="\033[0;36m"; C_OK="\033[0;32m"; C_WARN="\033[0;33m"; C_ERR="\033[0;31m"
else
  C_RESET=""; C_INFO=""; C_OK=""; C_WARN=""; C_ERR=""
fi

_ts() { date '+%Y-%m-%d %H:%M:%S'; }
log()  { echo -e "${C_INFO}[$(_ts)] •${C_RESET} $*"; }
ok()   { echo -e "${C_OK}[$(_ts)] ✓${C_RESET} $*"; }
warn() { echo -e "${C_WARN}[$(_ts)] ! ${C_RESET} $*" >&2; }
err()  { echo -e "${C_ERR}[$(_ts)] ✗${C_RESET} $*" >&2; }
die()  { err "$*"; exit 1; }

# Error trap: report which command failed and where.
on_error() {
  local exit_code=$?
  err "Failed (exit ${exit_code}) at ${BASH_SOURCE[1]:-?}:${BASH_LINENO[0]:-?} — command: ${BASH_COMMAND}"
  exit "${exit_code}"
}
trap on_error ERR

# --- Guards ----------------------------------------------------------------
require_root() {
  [[ "${EUID}" -eq 0 ]] || die "This script must run as root (use sudo)."
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Required command not found: $1"
}

is_installed() { dpkg -s "$1" >/dev/null 2>&1; }

# Idempotency markers — a stage records completion so re-runs are cheap/safe.
mark_done()  { mkdir -p "${STATE_DIR}"; touch "${STATE_DIR}/$1.done"; }
is_done()    { [[ -f "${STATE_DIR}/$1.done" ]]; }

# --- File helpers ----------------------------------------------------------
backup_file() {
  local f="$1"
  [[ -f "$f" ]] || return 0
  local bak="${f}.bak.$(date +%Y%m%d%H%M%S)"
  cp -a "$f" "$bak"
  log "Backed up ${f} -> ${bak}"
}

# Render a template, substituting ONLY the ${VARS} that are (a) referenced in the
# template AND (b) currently exported in our environment. This is deliberate:
# a bare `envsubst` blanks out every $-reference it finds, which would destroy
# nginx runtime vars ($uri, $query_string, ...) and prematurely resolve Laravel
# .env references like ${APP_NAME} that must stay literal.
render_template() {
  local src="$1" dst="$2"
  [[ -f "$src" ]] || die "Template not found: ${src}"
  backup_file "$dst"

  local v shellfmt="" referenced
  referenced="$(grep -oE '\$\{[A-Za-z_][A-Za-z0-9_]*\}' "$src" | tr -d '${}' | sort -u)"
  for v in ${referenced}; do
    if printenv "$v" >/dev/null 2>&1; then shellfmt+="\$${v} "; fi
  done

  envsubst "${shellfmt}" < "$src" > "$dst"
  ok "Rendered $(basename "$src") -> ${dst}"
}

# Idempotently ensure a line is present in a file.
ensure_line() {
  local line="$1" file="$2"
  touch "$file"
  grep -qxF "$line" "$file" || echo "$line" >> "$file"
}

# --- Secrets ---------------------------------------------------------------
# Generate an alphanumeric secret. Built by accumulating filtered openssl output
# rather than `tr </dev/urandom | head` — the latter closes the pipe mid-stream
# and trips SIGPIPE (141) under `set -o pipefail`.
generate_password() {
  local n="${1:-32}" s=""
  while (( ${#s} < n )); do
    s+="$(openssl rand -base64 48 | LC_ALL=C tr -dc 'A-Za-z0-9')"
  done
  printf '%s' "${s:0:n}"
}

# Persist a credential to the root-only secrets file (idempotent upsert).
save_credential() {
  local key="$1" value="$2"
  mkdir -p "$(dirname "${CREDENTIALS_FILE}")"
  touch "${CREDENTIALS_FILE}"; chmod 600 "${CREDENTIALS_FILE}"
  if grep -q "^${key}=" "${CREDENTIALS_FILE}" 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${value}|" "${CREDENTIALS_FILE}"
  else
    echo "${key}=${value}" >> "${CREDENTIALS_FILE}"
  fi
}

read_credential() {
  # Safe under `set -e`: missing file or no match returns empty, not an error.
  [[ -f "${CREDENTIALS_FILE}" ]] || return 0
  grep "^${1}=" "${CREDENTIALS_FILE}" 2>/dev/null | head -n1 | cut -d= -f2- || true
}

# Return the configured value, else the saved credential, else a freshly
# generated one (which is then persisted). Keeps re-runs stable.
resolve_secret() {
  local var_name="$1" length="${2:-32}" current saved
  current="${!var_name:-}"
  if [[ -n "${current}" ]]; then echo "${current}"; return; fi
  saved="$(read_credential "${var_name}")"
  if [[ -n "${saved}" ]]; then echo "${saved}"; return; fi
  local fresh; fresh="$(generate_password "${length}")"
  save_credential "${var_name}" "${fresh}"
  echo "${fresh}"
}

# Wait for background apt/dpkg locks (unattended-upgrades, apt-daily) to clear
# so our apt commands don't fail mid-provision.
wait_apt_lock() {
  local i=0
  while fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1 \
     || fuser /var/lib/apt/lists/lock >/dev/null 2>&1 \
     || fuser /var/cache/apt/archives/lock >/dev/null 2>&1; do
    i=$((i+1))
    (( i > 60 )) && { warn "apt lock still held after ~5min; proceeding anyway."; break; }
    log "Waiting for another apt/dpkg process to release the lock..."
    sleep 5
  done
}

apt_quiet() {
  wait_apt_lock
  DEBIAN_FRONTEND=noninteractive apt-get -y -o Dpkg::Options::="--force-confold" "$@"
}
