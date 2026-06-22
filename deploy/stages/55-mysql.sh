#!/usr/bin/env bash
# Stage 55 — MySQL 8: install, secure, create app DB + least-privilege user,
# and tune InnoDB for a 4GB host.

DB_ROOT_PASSWORD="$(resolve_secret DB_ROOT_PASSWORD 40)"
DB_PASSWORD="$(resolve_secret DB_PASSWORD 32)"
save_credential "DB_NAME" "${DB_NAME}"
save_credential "DB_USER" "${DB_USER}"

if ! is_installed mysql-server; then
  log "Installing MySQL 8 server..."
  apt_quiet install mysql-server
fi
systemctl enable --now mysql >/dev/null 2>&1 || systemctl enable --now mysqld >/dev/null 2>&1 || true

# Tuning config first so it's in effect after the restart below.
export MYSQL_INNODB_BUFFER_POOL MYSQL_MAX_CONNECTIONS
log "Writing InnoDB tuning..."
render_template "${TEMPLATES_DIR}/mysqld-tuning.cnf" "/etc/mysql/mysql.conf.d/99-tlm.cnf"

# --- Secure the root account (idempotent: works whether auth_socket or password). ---
log "Securing MySQL root + applying baseline hardening..."
SQL_ROOT="
ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${DB_ROOT_PASSWORD}';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
"
# Try password auth first (re-runs), fall back to socket auth (fresh install).
if mysql -uroot -p"${DB_ROOT_PASSWORD}" -e "SELECT 1" >/dev/null 2>&1; then
  mysql -uroot -p"${DB_ROOT_PASSWORD}" <<< "${SQL_ROOT}"
else
  mysql -uroot <<< "${SQL_ROOT}"
fi

# Root credentials file for non-interactive admin (chmod 600).
cat > /root/.my.cnf <<EOF
[client]
user=root
password=${DB_ROOT_PASSWORD}
EOF
chmod 600 /root/.my.cnf

# --- App database + least-privilege user (localhost only). ---
log "Creating database '${DB_NAME}' and user '${DB_USER}'@'127.0.0.1'..."
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED WITH caching_sha2_password BY '${DB_PASSWORD}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED WITH caching_sha2_password BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${DB_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES, CREATE TEMPORARY TABLES, EXECUTE, CREATE VIEW, SHOW VIEW
  ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES, CREATE TEMPORARY TABLES, EXECUTE, CREATE VIEW, SHOW VIEW
  ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

log "Restarting MySQL to apply tuning..."
systemctl restart mysql 2>/dev/null || systemctl restart mysqld
mysqladmin status >/dev/null 2>&1 && ok "MySQL up. DB '${DB_NAME}' ready; app user '${DB_USER}' (localhost-only)." \
  || warn "MySQL restarted but status check failed."
