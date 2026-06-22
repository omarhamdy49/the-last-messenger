#!/usr/bin/env bash
# Stage 80 — Supervisor: runs the queue workers AND the Laravel scheduler.
# The app defines schedules via the Schedule facade (routes/console.php), so a
# long-running `schedule:work` (Laravel 11+) is the clean fit — no crontab edits.

is_installed supervisor || apt_quiet install supervisor

PHP_BIN="/usr/bin/php${PHP_VERSION}"
ARTISAN="${APP_DIR}/current/artisan"
export PHP_BIN ARTISAN APP_DIR DEPLOY_USER QUEUE_WORKERS QUEUE_CONNECTION

log "Writing Supervisor programs (worker + scheduler)..."
render_template "${TEMPLATES_DIR}/supervisor-worker.conf"    "/etc/supervisor/conf.d/tlm-worker.conf"
render_template "${TEMPLATES_DIR}/supervisor-scheduler.conf" "/etc/supervisor/conf.d/tlm-scheduler.conf"

systemctl enable supervisor >/dev/null 2>&1 || true
systemctl restart supervisor
sleep 1
supervisorctl reread || true
supervisorctl update || true

# Workers won't start until a release exists at current/ — that's expected.
if [[ -e "${ARTISAN}" ]]; then
  supervisorctl restart all || true
  ok "Supervisor running. Status:"
  supervisorctl status || true
else
  warn "Supervisor configured. Workers will start after the first deploy creates ${ARTISAN}."
fi
