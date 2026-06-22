#!/usr/bin/env bash
# Stage 85 — logrotate for app + worker logs (Laravel daily logs are rotated by
# the framework; this covers Supervisor worker/scheduler + php-fpm slow logs).

cat > /etc/logrotate.d/tlm <<EOF
${APP_DIR}/shared/storage/logs/*.log {
    daily
    rotate 14
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
    su ${DEPLOY_USER} www-data
}

/var/log/php${PHP_VERSION}-fpm-slow.log {
    weekly
    rotate 8
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
}
EOF

logrotate --debug /etc/logrotate.d/tlm >/dev/null 2>&1 && ok "Log rotation configured." \
  || warn "logrotate config written but debug check reported issues."
