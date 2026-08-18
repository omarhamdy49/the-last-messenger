#!/usr/bin/env bash
# Sample production resource usage during a k6 run, as CSV on stdout.
#
#   tests/load/monitor.sh [duration_seconds] [interval_seconds]
#   tests/load/monitor.sh 900 5 > tests/load/results/monitor-$(date +%s).csv
#
# Runs locally and SSHes to the VPS once, holding a single connection for the
# whole sample loop — a fresh ssh per sample would itself skew the numbers.

set -euo pipefail

DURATION="${1:-600}"
INTERVAL="${2:-5}"
SSH_KEY="${SSH_KEY:-/Volumes/Data/work/projects/the-last-massenger/docs/rasoolallah-vps-ssh/ovh_vps}"
SSH_HOST="${SSH_HOST:-deployer@51.195.100.16}"

ssh -i "$SSH_KEY" -o ConnectTimeout=15 "$SSH_HOST" \
  "DURATION=$DURATION INTERVAL=$INTERVAL bash -s" <<'REMOTE'
set -u
cd /var/www/rasoolallah/current

# NOTE: the three mysql_* columns are in SHOW GLOBAL STATUS's alphabetical order
# (Slow_queries, Threads_connected, Threads_running) — the header matches that,
# not the order they appear in the IN() list.
echo "ts,mem_used_mb,mem_avail_mb,swap_used_mb,load1,mysqld_rss_mb,fpm_procs,fpm_rss_mb,fpm_sock_backlog,nginx_estab,mysql_slow_queries,mysql_threads_connected,mysql_threads_running,disk_used_pct"

# Credentials straight from .env — config/database.php calls env(), which is not
# defined outside a booted Laravel app, so requiring it here would fatal.
envval() { grep -m1 "^$1=" .env | cut -d= -f2- | tr -d '\042\047'; }   # strip " and '
DB_H=$(envval DB_HOST); DB_P=$(envval DB_PORT); DB_N=$(envval DB_DATABASE)
DB_U=$(envval DB_USERNAME); DB_W=$(envval DB_PASSWORD)

END=$(( $(date +%s) + DURATION ))
while [ "$(date +%s)" -lt "$END" ]; do
  read -r MEM_USED MEM_AVAIL <<<"$(free -m | awk '/^Mem:/{print $3, $7}')"
  SWAP_USED=$(free -m | awk '/^Swap:/{print $3}')
  LOAD1=$(awk '{print $1}' /proc/loadavg)

  MYSQL_RSS=$(ps -o rss= -C mysqld 2>/dev/null | awk '{s+=$1} END{printf "%.0f", s/1024}')
  read -r FPM_N FPM_RSS <<<"$(ps -eo rss,args 2>/dev/null | awk '/php-fpm: pool/{n++; s+=$1} END{printf "%d %.0f", n+0, s/1024}')"

  # Recv-Q on the fpm unix socket IS the listen queue: requests nginx has handed
  # over that no worker has picked up yet. Sustained >0 means max_children is the
  # ceiling. (The pool's pm.status_path has no nginx location block, so the
  # status page is unreachable — this is the equivalent signal.)
  FPM_Q=$(ss -lx 2>/dev/null | awk '/php8.5-fpm.sock/{print $3; exit}')
  NGX_EST=$(ss -tn state established '( sport = :443 or sport = :80 )' 2>/dev/null | tail -n +2 | wc -l)

  MYSQL=$(MYSQL_PWD="$DB_W" mysql -h"$DB_H" -P"$DB_P" -u"$DB_U" -N -B --connect-timeout=2 -e \
    "SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected','Threads_running','Slow_queries')" "$DB_N" 2>/dev/null \
    | awk '{printf "%s%s", sep, $2; sep=","}')
  [ -z "$MYSQL" ] && MYSQL=",,"

  DISK=$(df --output=pcent / | tail -1 | tr -dc '0-9')

  echo "$(date +%s),${MEM_USED},${MEM_AVAIL},${SWAP_USED},${LOAD1},${MYSQL_RSS:-0},${FPM_N:-0},${FPM_RSS:-0},${FPM_Q:-},${NGX_EST:-},${MYSQL},${DISK}"
  sleep "$INTERVAL"
done
REMOTE
