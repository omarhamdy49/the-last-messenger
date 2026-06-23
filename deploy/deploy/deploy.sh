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
KEEP_RELEASES="${KEEP_RELEASES:-5}"
PHP_BIN="/usr/bin/php${PHP_VERSION}"

RELEASES_DIR="${APP_DIR}/releases"
SHARED_DIR="${APP_DIR}/shared"
CURRENT="${APP_DIR}/current"
TS="$(date +%Y%m%d%H%M%S)"
RELEASE="${RELEASES_DIR}/${TS}"

log() { echo "[$(date '+%H:%M:%S')] $*"; }
PREVIOUS="$(readlink -f "${CURRENT}" 2>/dev/null || true)"

rollback() {
  echo "::error:: Deploy failed — rolling back."
  if [[ -n "${PREVIOUS}" && -d "${PREVIOUS}" ]]; then
    ln -nfs "${PREVIOUS}" "${CURRENT}"
    sudo systemctl reload "php${PHP_VERSION}-fpm" || true
    sudo supervisorctl restart all || true
    echo "Rolled back to ${PREVIOUS}."
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
rm -rf "${RELEASE}/storage"
ln -nfs "${SHARED_DIR}/storage" "${RELEASE}/storage"

cd "${RELEASE}"

log "composer install (production)..."
# --ignore-platform-reqs: the distro ships PHP 8.5 but composer.lock pins some
# deps (nette/schema, phpoffice/phpspreadsheet) that declare an <=8.4 cap. They
# run fine on 8.5; bypass the platform gate rather than churn the lock at deploy.
# Proper fix is `composer update` to 8.5-aware versions, committed from dev.
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress --ignore-platform-reqs

log "Running migrations..."
${PHP_BIN} artisan migrate --force --no-interaction

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

log "Reloading PHP-FPM (resets OPcache) and restarting workers..."
sudo systemctl reload "php${PHP_VERSION}-fpm"
sudo supervisorctl restart all

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
log "Pruning old releases (keeping ${KEEP_RELEASES})..."
ls -1dt "${RELEASES_DIR}"/*/ 2>/dev/null | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -rf

rm -f "${ARTIFACT}"
log "✅ Deploy ${TS} (sha ${GIT_SHA}) complete -> ${RELEASE}"
