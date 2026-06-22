#!/usr/bin/env bash
# Stage 25 — UFW firewall. Default-deny inbound; allow SSH + HTTP/HTTPS only.

is_installed ufw || apt_quiet install ufw

log "Configuring UFW (default deny inbound, allow outbound)..."
ufw --force reset >/dev/null
ufw default deny incoming
ufw default allow outgoing

# Rate-limit SSH (UFW throttles repeated connections from one IP).
ufw limit OpenSSH
ufw allow 80/tcp   comment 'HTTP'
ufw allow 443/tcp  comment 'HTTPS'

ufw --force enable
ufw status verbose
ok "Firewall active (22 limited, 80, 443 open; everything else denied)."
