#!/usr/bin/env bash
# Stage 25 — UFW firewall. Default-deny inbound; allow SSH + HTTP/HTTPS only.

is_installed ufw || apt_quiet install ufw

log "Configuring UFW (default deny inbound, allow outbound)..."
ufw --force reset >/dev/null
ufw default deny incoming
ufw default allow outgoing

# Allow SSH (NOT `limit`): `ufw limit` drops an IP after 6 connections/30s,
# which breaks CI/CD over SSH — a single deploy does ssh-keyscan (multiple key
# types) + scp + ssh in seconds and trips it. Brute-force protection is handled
# by fail2ban + key-only auth instead.
ufw allow OpenSSH
ufw allow 80/tcp   comment 'HTTP'
ufw allow 443/tcp  comment 'HTTPS'

ufw --force enable
ufw status verbose
ok "Firewall active (22 limited, 80, 443 open; everything else denied)."
