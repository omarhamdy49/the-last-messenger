#!/usr/bin/env bash
# Stage 80 — Supervisor: runs the queue workers AND the Laravel scheduler.
# The app defines schedules via the Schedule facade (routes/console.php), so a
# long-running `schedule:work` (Laravel 11+) is the clean fit — no crontab edits.

is_installed supervisor || apt_quiet install supervisor

PHP_BIN="/usr/bin/php${PHP_VERSION}"
ARTISAN="${APP_DIR}/current/artisan"

# YouTube imports run on their own queue with their own timings — see
# supervisor-sync-worker.conf for why they are not on the default queue.
SYNC_QUEUE_WORKERS="${SYNC_QUEUE_WORKERS:-1}"
SYNC_QUEUE_TIMEOUT="${SYNC_QUEUE_TIMEOUT:-3600}"
# Supervisor must wait longer than the job timeout or it kills imports mid-run.
SYNC_QUEUE_STOPWAIT="$(( SYNC_QUEUE_TIMEOUT + 60 ))"

# View tracking (RecordMediaViewJob) — bursty micro-jobs on their own queue so
# page-traffic spikes never delay default/sync work.
TRACKING_QUEUE_WORKERS="${TRACKING_QUEUE_WORKERS:-1}"

export PHP_BIN ARTISAN APP_DIR DEPLOY_USER QUEUE_WORKERS QUEUE_CONNECTION
export SYNC_QUEUE_WORKERS SYNC_QUEUE_TIMEOUT SYNC_QUEUE_STOPWAIT
export TRACKING_QUEUE_WORKERS

log "Writing Supervisor programs (worker + sync worker + tracking worker + scheduler)..."
render_template "${TEMPLATES_DIR}/supervisor-worker.conf"          "/etc/supervisor/conf.d/tlm-worker.conf"
render_template "${TEMPLATES_DIR}/supervisor-sync-worker.conf"     "/etc/supervisor/conf.d/tlm-sync-worker.conf"
render_template "${TEMPLATES_DIR}/supervisor-tracking-worker.conf" "/etc/supervisor/conf.d/tlm-tracking-worker.conf"
render_template "${TEMPLATES_DIR}/supervisor-scheduler.conf"       "/etc/supervisor/conf.d/tlm-scheduler.conf"

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
