#!/usr/bin/env bash
# Stage 10 — swap + kernel/VM tuning. Critical on a 4GB box so MySQL/PHP
# spikes don't trigger the OOM killer.

SWAP_FILE="/swapfile"
SWAP_SIZE_MB="${SWAP_SIZE_MB:-2048}"

if swapon --show | grep -q "${SWAP_FILE}"; then
  ok "Swap already active:"; swapon --show
else
  if [[ ! -f "${SWAP_FILE}" ]]; then
    log "Creating ${SWAP_SIZE_MB}MB swap file at ${SWAP_FILE}..."
    if ! fallocate -l "${SWAP_SIZE_MB}M" "${SWAP_FILE}" 2>/dev/null; then
      dd if=/dev/zero of="${SWAP_FILE}" bs=1M count="${SWAP_SIZE_MB}" status=progress
    fi
  fi
  chmod 600 "${SWAP_FILE}"
  mkswap "${SWAP_FILE}" >/dev/null
  swapon "${SWAP_FILE}"
  ok "Swap enabled."
fi
ensure_line "${SWAP_FILE} none swap sw 0 0" /etc/fstab

log "Applying VM/kernel sysctl tuning..."
cat > /etc/sysctl.d/99-tlm.conf <<'EOF'
# The Last Messenger — VM & network tuning (4GB single-app host)
vm.swappiness = 10
vm.vfs_cache_pressure = 50
vm.overcommit_memory = 1
# Network hardening + throughput
net.ipv4.tcp_syncookies = 1
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1
net.ipv4.icmp_echo_ignore_broadcasts = 1
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.all.accept_source_route = 0
net.ipv4.tcp_fin_timeout = 15
net.core.somaxconn = 1024
fs.file-max = 200000
EOF
sysctl --system >/dev/null

# Raise file descriptor limits for php-fpm/mysql/nginx under load.
cat > /etc/security/limits.d/99-tlm.conf <<'EOF'
*    soft nofile 65535
*    hard nofile 65535
root soft nofile 65535
root hard nofile 65535
EOF

ok "Swap + kernel tuning applied."
