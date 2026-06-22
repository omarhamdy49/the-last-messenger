#!/usr/bin/env bash
# Stage 30 — fail2ban: ban brute-force SSH (and nginx auth/abuse) sources.

is_installed fail2ban || apt_quiet install fail2ban

cat > /etc/fail2ban/jail.d/tlm.local <<'EOF'
[DEFAULT]
bantime  = 1h
findtime = 10m
maxretry = 5
backend  = systemd
# Never ban localhost.
ignoreip = 127.0.0.1/8 ::1

# Default mode bans on real auth failures only. NOT "aggressive": that also bans
# on "Connection closed [preauth]", which ssh-keyscan and health probes trigger —
# it would ban CI/CD runners and monitoring.
[sshd]
enabled = true

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled  = true
filter   = nginx-limit-req
logpath  = /var/log/nginx/error.log
maxretry = 10
findtime = 1m
bantime  = 1h

[nginx-botsearch]
enabled = true
EOF

systemctl enable --now fail2ban
systemctl restart fail2ban
sleep 1
fail2ban-client status >/dev/null 2>&1 && ok "fail2ban active." || warn "fail2ban started but status check failed."
