#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="$(dirname "${SCRIPT_DIR}")"
WORKSPACE_ROOT="$(dirname "${APP_ROOT}")"

SSH_KEY="${SSH_KEY:-${WORKSPACE_ROOT}/ovh_vps}"
SSH_HOST="${SSH_HOST:-51.195.100.16}"
SSH_USER="${SSH_USER:-deployer}"
REMOTE_APP="${REMOTE_APP:-/var/www/rasoolallah/current}"
BACKUP_DIR="${BACKUP_DIR:-${SCRIPT_DIR}/backups}"

if [[ "${1:-}" != "--yes" ]]; then
  read -r -p "Replace the production database with the local database? [y/N] " answer
  [[ "${answer}" == "y" || "${answer}" == "Y" ]] || exit 0
fi

for command in php mysqldump mysql gzip scp ssh; do
  command -v "${command}" >/dev/null || { echo "Missing command: ${command}" >&2; exit 1; }
done
[[ -f "${APP_ROOT}/.env" ]] || { echo "Missing ${APP_ROOT}/.env" >&2; exit 1; }
[[ -f "${SSH_KEY}" ]] || { echo "Missing SSH key: ${SSH_KEY}" >&2; exit 1; }

umask 077
mkdir -p "${BACKUP_DIR}"
LOCAL_CNF="$(mktemp "${TMPDIR:-/tmp}/tlm-mysql.XXXXXX")"
trap 'rm -f "${LOCAL_CNF}"' EXIT

cd "${APP_ROOT}"
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = config("database.connections." . config("database.default"));
$quote = static fn ($value) => "\"" . addcslashes((string) $value, "\\\"\n\r\t") . "\"";
echo "[client]\n";
foreach (["host" => "host", "port" => "port", "user" => "username", "password" => "password"] as $option => $key) {
    echo $option, "=", $quote($connection[$key] ?? ""), "\n";
}
' > "${LOCAL_CNF}"

LOCAL_DB="$(php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo config("database.connections." . config("database.default") . ".database");
')"
LOCAL_TABLES="$(mysql --defaults-extra-file="${LOCAL_CNF}" --batch --skip-column-names \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${LOCAL_DB}'")"

TIMESTAMP="$(date +%Y%m%d%H%M%S)"
LOCAL_DUMP="${BACKUP_DIR}/${LOCAL_DB}-${TIMESTAMP}.sql.gz"
REMOTE_DUMP="/tmp/tlm-db-import-${TIMESTAMP}.sql.gz"
SSH=(ssh -i "${SSH_KEY}" -o IdentitiesOnly=yes -o BatchMode=yes)
SCP=(scp -i "${SSH_KEY}" -o IdentitiesOnly=yes -o BatchMode=yes)

echo "Dumping local ${LOCAL_DB}..."
mysqldump --defaults-extra-file="${LOCAL_CNF}" \
  --single-transaction --quick --skip-lock-tables --hex-blob \
  --set-gtid-purged=OFF --no-tablespaces --default-character-set=utf8mb4 \
  "${LOCAL_DB}" | gzip -1 > "${LOCAL_DUMP}"
gzip -t "${LOCAL_DUMP}"
echo "Local dump: ${LOCAL_DUMP} ($(du -h "${LOCAL_DUMP}" | cut -f1))"

echo "Uploading dump..."
"${SCP[@]}" "${LOCAL_DUMP}" "${SSH_USER}@${SSH_HOST}:${REMOTE_DUMP}"

"${SSH[@]}" "${SSH_USER}@${SSH_HOST}" bash -s -- \
  "${REMOTE_DUMP}" "${REMOTE_APP}" "${TIMESTAMP}" "${LOCAL_TABLES}" <<'REMOTE'
set -Eeuo pipefail

REMOTE_DUMP="$1"
REMOTE_APP="$2"
TIMESTAMP="$3"
EXPECTED_TABLES="$4"
BASE_DIR="$(dirname "${REMOTE_APP}")"
BACKUP_DIR="${BASE_DIR}/shared/backups"
REMOTE_CNF="$(mktemp /tmp/tlm-mysql.XXXXXX)"
MAINTENANCE=0

cleanup() {
  rm -f "${REMOTE_CNF}" "${REMOTE_DUMP}"
  if [[ "${MAINTENANCE}" == 1 ]]; then
    php "${REMOTE_APP}/artisan" up >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

umask 077
mkdir -p "${BACKUP_DIR}"
cd "${REMOTE_APP}"
gzip -t "${REMOTE_DUMP}"

php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = config("database.connections." . config("database.default"));
$quote = static fn ($value) => "\"" . addcslashes((string) $value, "\\\"\n\r\t") . "\"";
echo "[client]\n";
foreach (["host" => "host", "port" => "port", "user" => "username", "password" => "password"] as $option => $key) {
    echo $option, "=", $quote($connection[$key] ?? ""), "\n";
}
' > "${REMOTE_CNF}"

DB_NAME="$(php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo config("database.connections." . config("database.default") . ".database");
')"

PRODUCTION_BACKUP="${BACKUP_DIR}/${DB_NAME}-before-import-${TIMESTAMP}.sql.gz"
echo "Backing up production to ${PRODUCTION_BACKUP}..."
mysqldump --defaults-extra-file="${REMOTE_CNF}" \
  --single-transaction --quick --skip-lock-tables --hex-blob \
  --set-gtid-purged=OFF --no-tablespaces --default-character-set=utf8mb4 \
  "${DB_NAME}" | gzip -1 > "${PRODUCTION_BACKUP}"
gzip -t "${PRODUCTION_BACKUP}"

php artisan down --retry=60
MAINTENANCE=1
echo "Dropping the existing production schema..."
{
  echo 'SET FOREIGN_KEY_CHECKS=0;'
  mysql --defaults-extra-file="${REMOTE_CNF}" --batch --skip-column-names -e \
    "SELECT CONCAT('DROP VIEW IF EXISTS ', CHAR(96), REPLACE(TABLE_NAME, CHAR(96), CONCAT(CHAR(96), CHAR(96))), CHAR(96), ';') FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_type='VIEW'"
  mysql --defaults-extra-file="${REMOTE_CNF}" --batch --skip-column-names -e \
    "SELECT CONCAT('DROP TABLE IF EXISTS ', CHAR(96), REPLACE(TABLE_NAME, CHAR(96), CONCAT(CHAR(96), CHAR(96))), CHAR(96), ';') FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE'"
  echo 'SET FOREIGN_KEY_CHECKS=1;'
} | mysql --defaults-extra-file="${REMOTE_CNF}" "${DB_NAME}"

echo "Importing local dump into ${DB_NAME}..."
gzip -dc "${REMOTE_DUMP}" | mysql --defaults-extra-file="${REMOTE_CNF}" \
  --default-character-set=utf8mb4 "${DB_NAME}"

php artisan migrate --force --no-interaction
php artisan optimize:clear

# Rebuild the caches optimize:clear just dropped — deploy.sh does this and this script did
# not, which left the box running with NO bootstrap/cache/config.php until the next code
# deploy happened to recreate it. That matters because php-fpm (www-data) may not be able
# to read shared/.env directly, in which case the config cache is the only thing standing
# between a cold cache and a site-wide 500. Cheap to run, and removes the asymmetry.
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

sudo supervisorctl restart all
php artisan up
MAINTENANCE=0

TABLES="$(mysql --defaults-extra-file="${REMOTE_CNF}" --batch --skip-column-names \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}'")"
[[ "${TABLES}" == "${EXPECTED_TABLES}" ]] || {
  echo "Table-count mismatch: local=${EXPECTED_TABLES}, production=${TABLES}" >&2
  exit 1
}
echo "Import complete: ${DB_NAME} has ${TABLES} tables."
REMOTE

echo "Database sync complete."
