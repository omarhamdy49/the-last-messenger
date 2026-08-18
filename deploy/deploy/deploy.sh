#!/usr/bin/env bash
# ===========================================================================
# deploy.sh — server-side, zero-downtime release deploy. Run AS the deploy user.
# Installed by provisioning to ${APP_DIR}/bin/deploy.sh and invoked by CI:
#
#   sudo -u deployer /var/www/rasoolallah/bin/deploy.sh /tmp/tlm-release.zip [git_sha]
#
# Flow: unpack -> link shared .env/storage -> composer install -> migrate
#       -> cache -> atomic symlink flip -> reload php-fpm -> restart workers
#       -> health probe (auto-rollback on failure) -> prune old releases.
# ===========================================================================
set -Eeuo pipefail

ARTIFACT="${1:?Usage: deploy.sh <artifact.zip> [git_sha]}"
GIT_SHA="${2:-manual}"

# Load layout config written by provisioning (overridable via env).
[[ -f /etc/tlm-deploy.env ]] && source /etc/tlm-deploy.env
APP_DIR="${APP_DIR:-/var/www/rasoolallah}"
PHP_VERSION="${PHP_VERSION:-8.4}"
PHP_BIN="/usr/bin/php${PHP_VERSION}"

RELEASES_DIR="${APP_DIR}/releases"
SHARED_DIR="${APP_DIR}/shared"
CURRENT="${APP_DIR}/current"
TS="$(date +%Y%m%d%H%M%S)"
RELEASE="${RELEASES_DIR}/${TS}"

log() { echo "[$(date '+%H:%M:%S')] $*"; }
# Direct symlink target (NOT readlink -f): on a first deploy `current` doesn't
# exist yet, and readlink -f would resolve to the path itself, making rollback
# create a self-referential `current -> current` symlink.
if [[ -L "${CURRENT}" ]]; then PREVIOUS="$(readlink "${CURRENT}")"; else PREVIOUS=""; fi

rollback() {
  echo "::error:: Deploy failed — rolling back."
  if [[ -n "${PREVIOUS}" && "${PREVIOUS}" != "${CURRENT}" && -d "${PREVIOUS}" && -e "${PREVIOUS}/artisan" ]]; then
    ln -nfs "${PREVIOUS}" "${CURRENT}"
    sudo systemctl reload "php${PHP_VERSION}-fpm" || true
    sudo supervisorctl restart all || true
    echo "Rolled back to ${PREVIOUS}."
  else
    echo "No valid previous release to roll back to (first deploy or none)."
  fi
  rm -rf "${RELEASE}"
  exit 1
}
trap rollback ERR

[[ -f "${ARTIFACT}" ]] || { echo "Artifact not found: ${ARTIFACT}"; exit 1; }

log "Unpacking release ${TS} (sha ${GIT_SHA})..."
mkdir -p "${RELEASE}"
unzip -q -o "${ARTIFACT}" -d "${RELEASE}"
# If the zip wrapped contents in a single top-level dir, flatten it.
if [[ ! -f "${RELEASE}/artisan" ]]; then
  inner="$(find "${RELEASE}" -maxdepth 2 -name artisan -printf '%h\n' | head -n1 || true)"
  [[ -n "${inner}" && "${inner}" != "${RELEASE}" ]] && { shopt -s dotglob; mv "${inner}"/* "${RELEASE}/"; shopt -u dotglob; }
fi
[[ -f "${RELEASE}/artisan" ]] || { echo "artisan not found in artifact"; exit 1; }

log "Linking shared .env and storage..."
ln -nfs "${SHARED_DIR}/.env" "${RELEASE}/.env"

# Re-assert .env mode/group on EVERY deploy, not just at provision time.
#
# Stage 75 sets 640 deployer:www-data, but any hand-edit of shared/.env resets it — a
# `>` redirect or an editor that writes-and-renames leaves it 600 deployer:deployer, and
# nothing put it back. That is not cosmetic: php-fpm runs as www-data, so with mode 600
# it cannot read .env AT ALL. The site keeps working only because config:cache below bakes
# everything into bootstrap/cache/config.php — which means the moment that cache is
# cleared (an admin clicking "Clear Config Cache", or optimize:clear from a script),
# every request loses APP_KEY and the DB credentials and the site 500s until someone SSHes
# in. Found live on 17 Aug 2026: mode was 600 and www-data could not read it.
#
# No sudo needed: deployer owns the file and is a member of www-data.
if [[ -f "${SHARED_DIR}/.env" ]]; then
  chgrp www-data "${SHARED_DIR}/.env" 2>/dev/null || true
  chmod 640 "${SHARED_DIR}/.env"
  log "  .env perms asserted: $(stat -c '%a %U:%G' "${SHARED_DIR}/.env")"
fi
rm -rf "${RELEASE}/storage"
ln -nfs "${SHARED_DIR}/storage" "${RELEASE}/storage"

cd "${RELEASE}"

# .env drift check. Stage 75 writes shared/.env only if it does not already exist, so every
# edit to templates/env.production since first provision has been a silent no-op on the
# server. That is how the live CACHE_STORE sat at `file` while the template said `redis`,
# and how DB_QUEUE_RETRY_AFTER went missing entirely — the latter meaning the queue
# considered a 3600s sync job abandoned after 90s and released it mid-run.
#
# Warn only, never fail: a missing key must not block a deploy, but it must stop being
# invisible.
ENV_TEMPLATE="${RELEASE}/deploy/templates/env.production"
if [[ -f "${ENV_TEMPLATE}" ]] && [[ -f "${SHARED_DIR}/.env" ]]; then
  missing=""
  while IFS= read -r key; do
    grep -qE "^${key}=" "${SHARED_DIR}/.env" || missing="${missing} ${key}"
  done < <(grep -oE '^[A-Z_][A-Z0-9_]*=' "${ENV_TEMPLATE}" | tr -d '=' | sort -u)

  if [[ -n "${missing}" ]]; then
    echo "WARNING: shared/.env is missing keys present in env.production:${missing}"
    echo "         Add them by hand, or re-run: sudo FORCE=1 ./provision.sh 75-app"
  else
    log "  .env has every key from env.production"
  fi
fi

log "composer install (production)..."
# composer.lock is resolved for PHP 8.5 (config.platform.php=8.5.4), so the
# platform gate passes natively — no --ignore-platform-reqs needed.
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress

log "Running migrations..."
${PHP_BIN} artisan migrate --force --no-interaction

# Site chrome that the public pages read from the database rather than from
# Blade — home hero/explore cards, quick-filter chips, menus. A migration adds
# the columns but not the rows, so without this a deploy renders those blocks
# empty. Every seeder listed here must stay idempotent: this runs on each deploy.
log "Seeding database-backed site chrome..."
${PHP_BIN} artisan db:seed --class=HomeModuleCardsSeeder --force --no-interaction
${PHP_BIN} artisan db:seed --class=HomeSectionsSeeder --force --no-interaction

log "Optimizing caches (config/route/view/event)..."
${PHP_BIN} artisan optimize:clear
${PHP_BIN} artisan config:cache
${PHP_BIN} artisan route:cache
${PHP_BIN} artisan view:cache
${PHP_BIN} artisan event:cache
# Public symlink for media (storage/app/public -> public/storage).
${PHP_BIN} artisan storage:link 2>/dev/null || true

log "Flipping 'current' symlink (atomic)..."
ln -nfs "${RELEASE}" "${CURRENT}"

log "Reloading PHP-FPM (resets OPcache) and cycling workers..."
sudo systemctl reload "php${PHP_VERSION}-fpm"

# Workers are cycled gracefully, NOT via `supervisorctl restart all`.
#
# A YouTube playlist import holds tlm-sync-worker for minutes, and that program
# sets stopwaitsecs to match. `restart all` would make every deploy block on the
# longest-running import (up to an hour) and then SIGKILL it, leaving a half
# imported program and a run stuck at "running".
#
# `queue:restart` flags workers to exit cleanly once their current job finishes;
# Supervisor's autorestart brings them straight back up on the new release.
${PHP_BIN} artisan queue:restart

# The scheduler holds no in-flight work, so a hard restart is safe and puts the
# new release's schedule in effect immediately.
#
# Non-fatal on purpose. This verb was added in 3f0211e but the NOPASSWD list in
# stages/15-user.sh predates it, so on a server provisioned before that stage is
# re-run sudo has no rule to match, asks for a password with no TTY, and fails
# the whole deploy *after* `current` has already been flipped. A stale scheduler
# is worth a warning, not a rollback of a release that is otherwise live.
sudo supervisorctl restart tlm-scheduler || {
  echo "::warning:: Could not restart tlm-scheduler (sudo rule missing?) — it keeps running the previous release until re-run stage 15-user."
}

# Reconcile Supervisor with any worker-config changes shipped in this release.
# `reread` picks up added/changed programs, `update` (re)starts them — both are
# in the deploy user's NOPASSWD allowlist. Guarded like the scheduler restart:
# the ERR trap is still armed here, so a missing sudo rule or a supervisor
# hiccup must warn, never roll back a release that is already live.
#
# This does NOT render conf files (writing /etc/supervisor/conf.d needs root and
# the queue env vars — that stays a provisioning concern, stage 80). So a
# brand-new worker still needs one `provision.sh 80-supervisor`; after that,
# every deploy keeps Supervisor in sync. The status check below turns the
# "conf never rendered" case from a silently unconsumed queue into a loud
# warning instead of a stuck import nobody notices.
sudo supervisorctl reread 2>/dev/null || echo "::warning:: supervisorctl reread failed (sudo rule missing? re-run stage 15-user)."
sudo supervisorctl update 2>/dev/null || echo "::warning:: supervisorctl update failed."
for prog in tlm-worker tlm-sync-worker tlm-tracking-worker tlm-scheduler; do
  sudo supervisorctl status "${prog}:*" 2>/dev/null | grep -q RUNNING \
    || sudo supervisorctl status "${prog}" 2>/dev/null | grep -q RUNNING \
    || echo "::warning:: Supervisor program '${prog}' is not RUNNING — run 'sudo FORCE=1 ./provision.sh 80-supervisor' on the server."
done

log "Health probe..."
${PHP_BIN} artisan about >/dev/null 2>&1 || { echo "artisan about failed"; exit 1; }
APP_DOMAIN="${APP_DOMAIN:-localhost}"
code=$(curl -kso /dev/null -w '%{http_code}' \
  --resolve "${APP_DOMAIN}:443:127.0.0.1" "https://${APP_DOMAIN}/" 2>/dev/null || echo 000)
case "${code}" in
  200|301|302) log "App responds (HTTP ${code})." ;;
  *) echo "App health probe returned HTTP ${code}"; exit 1 ;;
esac

trap - ERR
log "Pruning inactive releases (keeping current only)..."
current_release="$(readlink -f "${CURRENT}")"
for release in "${RELEASES_DIR}"/*/; do
  [[ -d "${release}" ]] || continue
  [[ "$(readlink -f "${release}")" == "${current_release}" ]] && continue
  # Best-effort: the release is already superseded and the new one is live
  # (symlink flipped, health probe passed above). A dir that won't fully
  # delete — e.g. a file php-fpm wrote as www-data that deployer can't remove —
  # is not worth failing an otherwise-live deploy. Warn, don't abort.
  # ponytail: cruft accumulates if this keeps warning; fix at the root by
  # chowning old releases to deployer if disk grows.
  rm -rf -- "${release}" || \
    echo "::warning:: Could not fully prune ${release} — left in place."
done

rm -f "${ARTIFACT}"
log "✅ Deploy ${TS} (sha ${GIT_SHA}) complete -> ${RELEASE}"
